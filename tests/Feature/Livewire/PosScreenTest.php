<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Orders\PosScreen;
use App\Models\User;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\PosDraft;
use App\Models\Order;
use App\Models\Permission;
use App\Models\UserRole;
use App\Models\UserRolePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PosScreenTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_calculates_totals_correctly_when_updating_cart(): void
    {
        $user = User::first();
        
        $customer = Customer::create([
            'name' => 'John Doe',
            'phone' => '1234567890',
            'email' => 'john@example.com',
            'is_active' => 1
        ]);

        $service = Service::create([
            'service_name' => 'Test Wash',
            'is_active' => 1,
            'icon' => 'default.png'
        ]);

        $serviceType = ServiceType::create([
            'service_type_name' => 'Premium',
            'is_active' => 1
        ]);

        \App\Models\ServiceDetail::create([
            'service_id' => $service->id,
            'service_type_id' => $serviceType->id,
            'service_price' => 50.0
        ]);

        Livewire::actingAs($user)
            ->test(PosScreen::class)
            ->set('customer_query', '1234567890')
            ->call('selectCustomer', $customer->id)
            ->call('selectService', $service->id)
            ->call('addItem')
            ->call('calculateTotal')
            ->assertSet('sub_total', 50.0);
    }

    public function test_it_automatically_saves_a_draft(): void
    {
        $user = User::first();
        
        $service = Service::create([
            'service_name' => 'Test Dry',
            'is_active' => 1,
            'icon' => 'default.png'
        ]);

        $serviceType = ServiceType::create([
            'service_type_name' => 'Premium Dry',
            'is_active' => 1
        ]);

        \App\Models\ServiceDetail::create([
            'service_id' => $service->id,
            'service_type_id' => $serviceType->id,
            'service_price' => 50.0
        ]);

        Livewire::actingAs($user)
            ->test(PosScreen::class)
            ->call('selectService', $service->id)
            ->call('addItem');
            // calculateTotal is called inside addItem

        $this->assertDatabaseHas('pos_drafts', [
            'user_id' => $user->id
        ]);
    }

    public function test_it_blocks_order_editing_without_the_order_edit_permission(): void
    {
        $role = UserRole::forceCreate(['name' => 'Order Creator']);
        $permission = Permission::where('name', 'order_create')->firstOrFail();
        UserRolePermission::forceCreate([
            'name' => $permission->name,
            'permission_name' => $permission->name,
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
        $user = User::create([
            'name' => 'Creator',
            'email' => 'creator@example.com',
            'password' => bcrypt('password'),
            'user_type' => 2,
            'role_id' => $role->id,
            'is_active' => 1,
        ]);
        $order = Order::create([
            'order_number' => 'ORD-AUTH',
            'order_date' => now(),
            'delivery_date' => now()->addDay(),
            'total' => 10,
            'status' => 0,
            'created_by' => User::firstOrFail()->id,
        ]);

        Livewire::actingAs($user)
            ->test(PosScreen::class, ['id' => $order->id])
            ->assertForbidden();
    }
}
