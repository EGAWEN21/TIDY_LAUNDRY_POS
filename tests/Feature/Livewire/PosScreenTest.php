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
        PosDraft::where('user_id', $user->id)->delete();

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
        PosDraft::where('user_id', $user->id)->delete();

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

    public function test_it_rejects_payment_amounts_above_the_current_balance(): void
    {
        $user = User::firstOrFail();
        PosDraft::where('user_id', $user->id)->delete();
        [$service, $serviceType] = $this->createCartItem(100);

        Livewire::actingAs($user)
            ->test(PosScreen::class)
            ->call('selectService', $service->id)
            ->call('addItem')
            ->set('payment_amount', 111)
            ->call('add_payment')
            ->assertHasErrors(['payment_amount']);
    }

    public function test_it_keeps_a_valid_partial_payment_in_component_state(): void
    {
        $user = User::firstOrFail();
        PosDraft::where('user_id', $user->id)->delete();
        [$service, $serviceType] = $this->createCartItem(100);

        Livewire::actingAs($user)
            ->test(PosScreen::class)
            ->call('selectService', $service->id)
            ->call('addItem')
            ->set('payment_amount', 40)
            ->set('payment_type', 1)
            ->set('notes', 'Deposit')
            ->call('add_payment')
            ->assertHasNoErrors()
            ->assertSet('payments.0.amount', 40)
            ->assertSet('payments.0.payment_type', 1)
            ->assertSet('payments.0.notes', 'Deposit');
    }

    public function test_it_blocks_new_orders_without_the_order_create_permission(): void
    {
        $role = UserRole::forceCreate(['name' => 'No Order Create']);
        $user = User::create([
            'name' => 'No Order Create User',
            'email' => 'no-order-create-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 2,
            'role_id' => $role->id,
            'is_active' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(PosScreen::class)
            ->assertForbidden();
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

    private function createCartItem(float $price): array
    {
        $service = Service::create([
            'service_name' => 'Payment Service ' . uniqid(),
            'is_active' => 1,
            'icon' => 'default.png',
        ]);
        $serviceType = ServiceType::create([
            'service_type_name' => 'Payment Type ' . uniqid(),
            'is_active' => 1,
        ]);
        \App\Models\ServiceDetail::create([
            'service_id' => $service->id,
            'service_type_id' => $serviceType->id,
            'service_price' => $price,
        ]);

        return [$service, $serviceType];
    }
}
