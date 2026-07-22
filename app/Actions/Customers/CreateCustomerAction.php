<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use App\DTOs\CustomerData;
use Illuminate\Support\Facades\DB;
use Exception;

class CreateCustomerAction
{
    /**
     * Create a new customer statelessly.
     *
     * @param CustomerData $data
     * @param int|null $userId
     * @return Customer
     */
    public static function execute(CustomerData $data, ?int $userId = null): Customer
    {
        return DB::transaction(function () use ($data, $userId) {
            $customer = new Customer();
            
            $customer->name = $data->name;
            $customer->phone = $data->phone;
            $customer->email = $data->email;
            $customer->tax_number = $data->tax_number;
            $customer->address = $data->address;
            
            $customer->created_by = $userId;
            $customer->is_active = $data->is_active;
            
            $customer->save();
            
            return $customer;
        });
    }
}
