<?php

namespace Tests\Feature\Customers;

use App\Actions\Customers\CreateCustomerAction;
use App\Actions\Customers\UpdateCustomerAction;
use App\DTOs\CustomerData;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerActionsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_creates_a_customer_with_all_customer_fields(): void
    {
        $user = $this->createUser();

        $customer = CreateCustomerAction::execute(
            new CustomerData(
                name: 'Action Customer',
                phone: '0800000001',
                email: 'action@example.com',
                tax_number: 'TAX-001',
                address: '1 Action Street',
                is_active: 1,
            ),
            $user->id,
        );

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Action Customer',
            'phone' => '0800000001',
            'email' => 'action@example.com',
            'tax_number' => 'TAX-001',
            'address' => '1 Action Street',
            'is_active' => 1,
            'created_by' => $user->id,
        ]);
    }

    public function test_it_updates_a_customer_with_all_editable_fields(): void
    {
        $customer = Customer::create([
            'name' => 'Original Customer',
            'phone' => '0800000002',
            'email' => 'original@example.com',
            'tax_number' => 'TAX-002',
            'address' => 'Original Street',
            'is_active' => 1,
        ]);

        $updated = UpdateCustomerAction::execute(
            $customer,
            new CustomerData(
                name: 'Updated Customer',
                phone: '0800000003',
                email: 'updated@example.com',
                tax_number: 'TAX-003',
                address: 'Updated Street',
                is_active: 0,
            ),
        );

        $this->assertSame($customer->id, $updated->id);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
            'phone' => '0800000003',
            'email' => 'updated@example.com',
            'tax_number' => 'TAX-003',
            'address' => 'Updated Street',
            'is_active' => 0,
        ]);
    }

    public function test_customer_data_maps_snake_case_payload_fields(): void
    {
        $data = CustomerData::from([
            'name' => 'Mapped Customer',
            'phone' => '0800000004',
            'tax_number' => 'TAX-004',
            'is_active' => 1,
        ]);

        $this->assertSame('Mapped Customer', $data->name);
        $this->assertSame('0800000004', $data->phone);
        $this->assertSame('TAX-004', $data->tax_number);
        $this->assertSame(1, $data->is_active);
        $this->assertNull($data->email);
        $this->assertNull($data->address);
    }

    private function createUser(): User
    {
        $email = Str::uuid().'@example.com';

        return User::create([
            'name' => 'Customer Action User',
            'email' => $email,
            'password' => bcrypt('password'),
            'user_type' => 1,
            'is_active' => 1,
        ]);
    }
}
