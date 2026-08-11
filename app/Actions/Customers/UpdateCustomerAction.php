<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use App\DTOs\CustomerData;
use Illuminate\Support\Facades\DB;

class UpdateCustomerAction
{
    /**
     * Update an existing customer statelessly.
     *
     * @param Customer $customer
     * @param CustomerData $data
     * @param int|null $userId
     * @return Customer
     */
    public static function execute(Customer $customer, CustomerData $data, ?int $userId = null): Customer
    {
        return DB::transaction(function () use ($customer, $data, $userId) {
            $customer->name = $data->name;
            $customer->phone = $data->phone;
            $customer->email = $data->email;
            $customer->tax_number = $data->tax_number;
            $customer->address = $data->address;
            $customer->is_active = $data->is_active;

            if ($userId !== null) {
                $customer->created_by = $userId;
            }

            $customer->save();

            return $customer;
        });
    }
}
