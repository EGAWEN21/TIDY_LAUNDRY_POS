<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserRolePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncCustomersApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_persists_an_offline_uuid_when_creating_a_customer(): void
    {
        $user = $this->createPosUser(['customer_create']);
        $uuid = (string) Str::uuid();

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/pos/sync-customers', [
                'customers' => [$this->payload($uuid)],
            ])
            ->assertOk()
            ->assertJsonPath("data.canonical_customer_uuids.{$uuid}", $uuid)
            ->assertJsonMissingPath("data.failed.{$uuid}");

        $this->assertDatabaseHas('customers', [
            'uuid' => $uuid,
            'phone' => '0801000001',
            'created_by' => $user->id,
        ]);
    }

    public function test_unchanged_phone_replay_preserves_canonical_uuid_without_edit_permission(): void
    {
        $user = $this->createPosUser([]);
        $canonicalUuid = (string) Str::uuid();
        $localUuid = (string) Str::uuid();
        $customer = Customer::create([
            'uuid' => $canonicalUuid,
            'name' => 'Offline Customer',
            'phone' => '0801000001',
            'is_active' => 1,
            'created_by' => $user->id,
        ]);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/pos/sync-customers', [
                'customers' => [$this->payload($localUuid)],
            ])
            ->assertOk()
            ->assertJsonPath("data.synced_customers.{$localUuid}", $customer->id)
            ->assertJsonPath("data.canonical_customer_uuids.{$localUuid}", $canonicalUuid)
            ->assertJsonMissingPath("data.failed.{$localUuid}");

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'uuid' => $canonicalUuid,
        ]);
        $this->assertDatabaseMissing('customers', ['uuid' => $localUuid]);
    }

    public function test_profile_change_requires_customer_edit_permission(): void
    {
        $user = $this->createPosUser([]);
        $canonicalUuid = (string) Str::uuid();
        $localUuid = (string) Str::uuid();
        $customer = Customer::create([
            'uuid' => $canonicalUuid,
            'name' => 'Canonical Name',
            'phone' => '0801000001',
            'is_active' => 1,
            'created_by' => $user->id,
        ]);

        $payload = $this->payload($localUuid);
        $payload['name'] = 'Unauthorized Change';

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/pos/sync-customers', ['customers' => [$payload]])
            ->assertOk()
            ->assertJsonPath(
                "data.failed.{$localUuid}",
                'Customer Sync Error: Missing customer_edit permission. Cannot overwrite an existing customer profile.',
            );

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'uuid' => $canonicalUuid,
            'name' => 'Canonical Name',
        ]);
    }

    public function test_authorized_profile_change_preserves_the_canonical_uuid(): void
    {
        $user = $this->createPosUser(['customer_edit']);
        $canonicalUuid = (string) Str::uuid();
        $localUuid = (string) Str::uuid();
        $customer = Customer::create([
            'uuid' => $canonicalUuid,
            'name' => 'Canonical Name',
            'phone' => '0801000001',
            'is_active' => 1,
            'created_by' => $user->id,
        ]);
        $payload = $this->payload($localUuid);
        $payload['name'] = 'Authorized Change';

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/pos/sync-customers', ['customers' => [$payload]])
            ->assertOk()
            ->assertJsonPath("data.canonical_customer_uuids.{$localUuid}", $canonicalUuid)
            ->assertJsonMissingPath("data.failed.{$localUuid}");

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'uuid' => $canonicalUuid,
            'name' => 'Authorized Change',
        ]);
        $this->assertDatabaseMissing('customers', ['uuid' => $localUuid]);
    }

    public function test_it_rejects_a_hidden_phone_match_without_creating_a_duplicate(): void
    {
        $owner = $this->createPosUser(['customer_create']);
        $actor = $this->createPosUser(['customer_create', 'customer_edit']);
        $hiddenUuid = (string) Str::uuid();
        $localUuid = (string) Str::uuid();
        Customer::create([
            'uuid' => $hiddenUuid,
            'name' => 'Hidden Customer',
            'phone' => '0801000001',
            'is_active' => 1,
            'created_by' => $owner->id,
        ]);

        $this->withToken($this->tokenFor($actor))
            ->postJson('/api/pos/sync-customers', [
                'customers' => [$this->payload($localUuid)],
            ])
            ->assertOk()
            ->assertJsonPath(
                "data.failed.{$localUuid}",
                'Customer Sync Error: The synchronized phone number belongs to a customer outside your customer visibility.',
            );

        $this->assertSame(1, Customer::where('phone', '0801000001')->count());
        $this->assertDatabaseMissing('customers', ['uuid' => $localUuid]);
    }

    public function test_new_customer_requires_customer_create_permission(): void
    {
        $user = $this->createPosUser([]);
        $uuid = (string) Str::uuid();

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/pos/sync-customers', [
                'customers' => [$this->payload($uuid)],
            ])
            ->assertOk()
            ->assertJsonPath(
                "data.failed.{$uuid}",
                'Customer Sync Error: Missing customer_create permission.',
            );

        $this->assertDatabaseMissing('customers', ['uuid' => $uuid]);
    }

    private function createPosUser(array $permissions): User
    {
        $role = UserRole::forceCreate(['name' => 'POS '.Str::uuid()]);
        foreach (array_unique(['order_create', ...$permissions]) as $permissionName) {
            $permission = Permission::where('name', $permissionName)->firstOrFail();
            UserRolePermission::forceCreate([
                'name' => $permission->name,
                'permission_name' => $permission->name,
                'role_id' => $role->id,
                'permission_id' => $permission->id,
            ]);
        }

        return User::create([
            'name' => 'POS User',
            'email' => Str::uuid().'@example.com',
            'password' => bcrypt('password'),
            'user_type' => 2,
            'role_id' => $role->id,
            'is_active' => 1,
        ]);
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('pos-token', ['pos:access'])->plainTextToken;
    }

    private function payload(string $uuid): array
    {
        return [
            'uuid' => $uuid,
            'name' => 'Offline Customer',
            'phone' => '0801000001',
        ];
    }
}
