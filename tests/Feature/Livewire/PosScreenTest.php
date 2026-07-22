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
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PosScreenTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_calculates_totals_correctly_when_updating_cart()
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

    /** @test */
    public function it_automatically_saves_a_draft()
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
}
