<?php

namespace Tests\Unit;

use App\DTOs\OrderData;
use App\DTOs\CartItemData;
use Spatie\LaravelData\DataCollection;
use Tests\TestCase;

class OrderDataTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated_from_an_array()
    {
        $payload = [
            'customer_id' => 1,
            'customer_name' => 'John Doe',
            'phone_number' => '1234567890',
            'order_date' => '2026-07-21',
            'delivery_date' => '2026-07-25',
            'sub_total' => 100.0,
            'addon_total' => 0.0,
            'discount' => 0.0,
            'tax_percentage' => 10.0,
            'tax_amount' => 10.0,
            'tax_type' => 1,
            'taxable_amount' => 100.0,
            'total' => 110.0,
            'note' => 'Test note',
            'status' => 0,
            'details' => [
                [
                    'service_id' => 1,
                    'service_name' => 'Wash',
                    'service_quantity' => 2,
                    'service_price' => 50.0,
                    'service_detail_total' => 100.0,
                ]
            ],
            'addons' => [],
            'payments' => []
        ];

        $orderData = OrderData::from($payload);

        $this->assertEquals(1, $orderData->customer_id);
        $this->assertEquals('John Doe', $orderData->customer_name);
        $this->assertEquals('2026-07-21', $orderData->order_date);
        $this->assertEquals(110.0, $orderData->total);
        $this->assertInstanceOf(DataCollection::class, $orderData->details);
        $this->assertCount(1, $orderData->details);
        
        $item = $orderData->details->first();
        $this->assertInstanceOf(CartItemData::class, $item);
        $this->assertEquals(1, $item->service_id);
        $this->assertEquals(100.0, $item->service_detail_total);
    }
}
