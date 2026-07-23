<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\Addon;
use App\DTOs\OrderData;
use App\Actions\Orders\CreateOrderAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateOrderActionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_creates_a_new_order_and_its_relations(): void
    {
        $user = User::first();
        $this->actingAs($user);

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

        $addon = Addon::create([
            'addon_name' => 'Softener',
            'addon_price' => 10.0,
            'is_active' => 1
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'phone_number' => $customer->phone,
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->addDays(2)->toDateString(),
            'sub_total' => 100.0,
            'addon_total' => 10.0,
            'discount' => 0.0,
            'tax_percentage' => 10.0,
            'tax_amount' => 11.0,
            'tax_type' => 1,
            'taxable_amount' => 110.0,
            'total' => 121.0,
            'note' => 'Test',
            'status' => 0,
            'details' => [
                [
                    'service_id' => $service->id,
                    'service_name' => $serviceType->service_type_name,
                    'service_quantity' => 2,
                    'service_price' => 50.0,
                    'service_detail_total' => 100.0,
                ]
            ],
            'addons' => [
                [
                    'addon_id' => $addon->id,
                    'addon_name' => $addon->addon_name,
                    'addon_price' => $addon->addon_price,
                ]
            ],
            'payments' => [
                [
                    'payment_type' => 1, // Cash
                    'amount' => 121.0,
                    'notes' => 'Paid'
                ]
            ]
        ];

        $orderDto = OrderData::from($payload);
        $order = CreateOrderAction::execute($orderDto, $user->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_id' => $customer->id,
            'tax_type' => 1,
            'taxable_amount' => 110.0,
            'total' => 121.0,
            'note' => 'Test',
        ]);

        $this->assertDatabaseHas('order_details', [
            'order_id' => $order->id,
            'service_id' => $service->id,
            'service_price' => 50.0,
        ]);

        $this->assertDatabaseHas('order_addon_details', [
            'order_id' => $order->id,
            'addon_id' => $addon->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'received_amount' => 121.0,
            'payment_note' => 'Paid',
        ]);
    }

    public function test_it_rejects_negative_payments_when_creating_an_order(): void
    {
        $user = User::firstOrFail();
        $dto = OrderData::from([
            'order_date' => now()->toDateString(),
            'delivery_date' => now()->addDay()->toDateString(),
            'sub_total' => 10.0,
            'addon_total' => 0.0,
            'discount' => 0.0,
            'tax_percentage' => 0.0,
            'tax_amount' => 0.0,
            'tax_type' => 1,
            'taxable_amount' => 10.0,
            'total' => 10.0,
            'details' => [],
            'addons' => [],
            'payments' => [[
                'payment_type' => 1,
                'amount' => -1.0,
            ]],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Negative payment amounts are not allowed');

        CreateOrderAction::execute($dto, $user->id);
    }
}
