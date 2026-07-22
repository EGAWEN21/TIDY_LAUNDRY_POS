<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\ServiceType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SyncOfflineOrdersApiTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_secures_the_sync_orders_api_endpoint()
    {
        $response = $this->postJson('/api/pos/sync-orders', [
            'orders' => []
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_successfully_syncs_a_valid_offline_order()
    {
        $user = User::create([
            'name' => 'API User',
            'email' => 'api@example.com',
            'password' => bcrypt('password'),
            'user_type' => 1,
            'is_active' => 1,
        ]);
        
        $service = Service::create([
            'service_name' => 'Wash',
            'is_active' => 1,
            'icon' => 'default.png'
        ]);

        $serviceType = ServiceType::create([
            'service_type_name' => 'Standard',
            'is_active' => 1
        ]);

        $payload = [
            'orders' => [
                [
                    'uuid' => 'dummy-uuid-1234',
                    'new_customer' => [
                        'name' => 'Offline Jane',
                        'phone' => '0987654321',
                        'uuid' => 'customer-uuid-123'
                    ],
                    'order_date' => now()->toDateString(),
                    'delivery_date' => now()->addDays(2)->toDateString(),
                    'sub_total' => 100.0,
                    'addon_total' => 0.0,
                    'discount' => 0.0,
                    'tax_percentage' => 0.0,
                    'tax_amount' => 0.0,
                    'tax_type' => 1,
                    'taxable_amount' => 100.0,
                    'total' => 100.0,
                    'note' => 'Offline test',
                    'status' => 0,
                    'details' => [
                        [
                            'service_id' => $service->id,
                            'service_name' => $serviceType->service_type_name,
                            'service_quantity' => 1,
                            'service_price' => 100.0,
                            'service_detail_total' => 100.0,
                        ]
                    ],
                    'addons' => [],
                    'payments' => []
                ]
            ]
        ];

        $token = $user->createToken('pos-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/pos/sync-orders', $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'synced_orders',
            'requires_approval',
            'failed'
        ]);
        
        $json = $response->json();
        
        // Assert that the customer was created
        $this->assertDatabaseHas('customers', [
            'phone' => '0987654321',
            'name' => 'Offline Jane'
        ]);

        // Since it's a Super Admin (user_type = 1), it should bypass approval and create the order directly
        $this->assertArrayHasKey('dummy-uuid-1234', $json['requires_approval']);
        $this->assertFalse($json['requires_approval']['dummy-uuid-1234']);
        
        $this->assertDatabaseHas('orders', [
            'uuid' => 'dummy-uuid-1234'
        ]);
    }
}
