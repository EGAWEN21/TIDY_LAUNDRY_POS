<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Actions\Orders\CalculateCartTotals;
use App\DTOs\CartItemData;
use Illuminate\Support\Facades\Cache;

class CalculateCartTotalsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_calculates_totals_with_exclusive_tax()
    {
        // Mock the Cache for MasterSettings
        Cache::shouldReceive('rememberForever')
            ->with('master_settings', \Closure::class)
            ->andReturn([
                'default_tax_percentage' => '10',
                'default_tax_mode' => '1', // Exclusive
            ]);
        $cartItems = [
            new CartItemData(1, 100.0, 2, 200.0, 'Wash'),
            new CartItemData(2, 50.0, 1, 50.0, 'Dry'),
        ];
        // Subtotal = 250
        // Addon = 50
        // Gross = 300
        // Tax (10% Exclusive) = 30
        // Discount = 10
        // Total = 300 + 30 - 10 = 320

        $result = CalculateCartTotals::execute($cartItems, 50.0, 10.0);

        $this->assertEquals(250.0, $result['sub_total']);
        $this->assertEquals(50.0, $result['addon_total']);
        $this->assertEquals(10.0, $result['tax_percentage']);
        $this->assertEquals(30.0, $result['tax_amount']);
        $this->assertEquals(1, $result['tax_type']);
        $this->assertEquals(300.0, $result['taxable_amount']);
        $this->assertEquals(10.0, $result['discount']);
        $this->assertEquals(320.0, $result['total']);
    }

    /** @test */
    public function it_calculates_totals_with_inclusive_tax()
    {
        Cache::shouldReceive('rememberForever')
            ->with('master_settings', \Closure::class)
            ->andReturn([
                'default_tax_percentage' => '10',
                'default_tax_mode' => '2', // Inclusive
            ]);

        $cartItems = [
            new CartItemData(1, 110.0, 2, 220.0, 'Wash'), // 220
            new CartItemData(2, 110.0, 1, 110.0, 'Dry'), // 110
        ];
        // Subtotal = 330
        // Addon = 0
        // Gross = 330
        // Tax (10% Inclusive): TaxFree = 330 * (100/110) = 300. TaxAmount = 30.
        // Discount = 0
        // Total = 330 - 0 = 330

        $result = CalculateCartTotals::execute($cartItems, 0.0, 0.0);

        $this->assertEquals(330.0, $result['sub_total']);
        $this->assertEquals(0.0, $result['addon_total']);
        $this->assertEquals(10.0, $result['tax_percentage']);
        $this->assertEquals(30.0, $result['tax_amount']);
        $this->assertEquals(2, $result['tax_type']);
        $this->assertEquals(300.0, $result['taxable_amount']);
        $this->assertEquals(330.0, $result['total']);
    }

    /** @test */
    public function it_supports_legacy_array_data_fallback()
    {
        Cache::shouldReceive('rememberForever')
            ->with('master_settings', \Closure::class)
            ->andReturn([
                'default_tax_percentage' => '10',
                'default_tax_mode' => '1', // Exclusive
            ]);

        $cartItems = [
            ['price' => 100.0, 'quantity' => 2],
        ];
        // Subtotal = 200
        // Tax (10% Exclusive) = 20
        // Total = 220

        $result = CalculateCartTotals::execute($cartItems, 0.0, 0.0);

        $this->assertEquals(200.0, $result['sub_total']);
        $this->assertEquals(20.0, $result['tax_amount']);
        $this->assertEquals(220.0, $result['total']);
    }
}
