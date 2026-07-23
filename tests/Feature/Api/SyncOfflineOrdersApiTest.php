<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\ServiceDetail;
use App\Models\UserRole;
use App\Models\OrderRequest;
use App\Models\Permission;
use App\Models\UserRolePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SyncOfflineOrdersApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_secures_the_sync_orders_api_endpoint(): void
    {
        $response = $this->postJson('/api/pos/sync-orders', [
            'orders' => []
        ]);

        $response->assertStatus(401);
    }

    public function test_it_successfully_syncs_a_valid_offline_order(): void
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

    public function test_it_creates_a_numbered_request_when_staff_requires_approval(): void
    {
        $role = UserRole::forceCreate(['name' => 'Cashier']);
        $this->grantOrderCreatePermission($role);
        $user = User::create([
            'name' => 'Cashier',
            'email' => 'cashier@example.com',
            'password' => bcrypt('password'),
            'user_type' => 2,
            'role_id' => $role->id,
            'is_active' => 1,
        ]);
        $service = Service::create([
            'service_name' => 'Wash',
            'is_active' => 1,
            'icon' => 'default.png',
        ]);
        $serviceType = ServiceType::create([
            'service_type_name' => 'Standard',
            'is_active' => 1,
        ]);
        ServiceDetail::create([
            'service_id' => $service->id,
            'service_type_id' => $serviceType->id,
            'service_price' => 25,
        ]);
        $uuid = 'approval-required-order';
        $token = $user->createToken('pos-token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/pos/sync-orders', [
            'orders' => [[
                'uuid' => $uuid,
                'new_customer' => [
                    'name' => 'Offline Customer',
                    'phone' => '5551000',
                    'uuid' => 'offline-customer-uuid',
                ],
                'order_date' => now()->toDateString(),
                'delivery_date' => now()->addDay()->toDateString(),
                'sub_total' => 25,
                'addon_total' => 0,
                'discount' => 0,
                'tax_percentage' => 0,
                'tax_amount' => 0,
                'tax_type' => 1,
                'taxable_amount' => 25,
                'total' => 25,
                'status' => 0,
                'details' => [[
                    'service_id' => $service->id,
                    'service_name' => $serviceType->service_type_name,
                    'service_quantity' => 1,
                    'service_price' => 25,
                    'service_detail_total' => 25,
                ]],
                'addons' => [],
                'payments' => [],
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath("requires_approval.{$uuid}", true)
            ->assertJsonPath('failed', []);
        $this->assertDatabaseHas('order_requests', [
            'uuid' => $uuid,
            'request_number' => 'REQ-0001',
            'status' => 0,
        ]);
    }

    public function test_it_returns_rejected_requests_with_the_canonical_reason(): void
    {
        $role = UserRole::forceCreate(['name' => 'Rejected Request Cashier']);
        $this->grantOrderCreatePermission($role);
        $user = User::create([
            'name' => 'Rejected Request Owner',
            'email' => 'rejected-owner@example.com',
            'password' => bcrypt('password'),
            'user_type' => 2,
            'role_id' => $role->id,
            'is_active' => 1,
        ]);
        OrderRequest::create([
            'request_number' => 'REQ-REJECTED',
            'created_by' => $user->id,
            'total_amount' => 10,
            'payload' => [],
            'status' => 2,
            'rejection_reason' => 'Correct the customer details',
            'rejection_note' => 'Correct the customer details',
            'uuid' => 'rejected-request-uuid',
        ]);
        $token = $user->createToken('pos-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/pos/rejected-orders')
            ->assertOk()
            ->assertJsonPath('rejected_orders.0.uuid', 'rejected-request-uuid')
            ->assertJsonPath('rejected_orders.0.rejection_reason', 'Correct the customer details');
    }

    private function grantOrderCreatePermission(UserRole $role): void
    {
        $permission = Permission::where('name', 'order_create')->firstOrFail();

        UserRolePermission::forceCreate([
            'name' => $permission->name,
            'permission_name' => $permission->name,
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
    }
}
