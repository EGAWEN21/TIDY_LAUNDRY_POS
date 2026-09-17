<?php

namespace App\Actions\Customers;

use App\DTOs\CustomerData;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReconcileOfflineCustomerAction
{
    public static function execute(CustomerData $data, User $user): Customer
    {
        return DB::transaction(function () use ($data, $user): Customer {
            $uuidMatch = $data->uuid
                ? Customer::query()->where('uuid', $data->uuid)->lockForUpdate()->first()
                : null;
            $phoneMatches = Customer::query()
                ->where('phone', $data->phone)
                ->lockForUpdate()
                ->get();

            if ($uuidMatch && ! self::isVisibleTo($uuidMatch, $user)) {
                self::fail('The synchronized customer is outside your customer visibility.');
            }

            if ($phoneMatches->contains(fn (Customer $customer): bool => ! self::isVisibleTo($customer, $user))) {
                self::fail('The synchronized phone number belongs to a customer outside your customer visibility.');
            }

            if ($phoneMatches->count() > 1) {
                self::fail('The synchronized phone number matches multiple customer profiles.');
            }

            $phoneMatch = $phoneMatches->first();
            if ($uuidMatch && $phoneMatch && ! $uuidMatch->is($phoneMatch)) {
                self::fail('The synchronized UUID and phone number identify different customers.');
            }

            $customer = $uuidMatch ?? $phoneMatch;
            if (! $customer) {
                if (! $user->hasPermission('customer_create')) {
                    self::fail('Missing customer_create permission.');
                }

                return CreateCustomerAction::execute($data, $user->id);
            }

            if (! self::profileMatches($customer, $data)) {
                if (! $user->hasPermission('customer_edit')) {
                    self::fail('Missing customer_edit permission. Cannot overwrite an existing customer profile.');
                }

                $customer->name = $data->name;
                $customer->phone = $data->phone;
                $customer->email = $data->email ?? $customer->email;
                $customer->tax_number = $data->tax_number ?? $customer->tax_number;
                $customer->address = $data->address ?? $customer->address;
            }

            if ($customer->uuid === null && $data->uuid !== null) {
                $customer->uuid = $data->uuid;
            }

            if ($customer->isDirty()) {
                $customer->save();
            }

            return $customer;
        });
    }

    private static function isVisibleTo(Customer $customer, User $user): bool
    {
        $viewableUserIds = $user->getViewableCustomerUserIds();

        return $viewableUserIds === 'all'
            || in_array((string) $customer->created_by, array_map('strval', $viewableUserIds), true);
    }

    private static function profileMatches(Customer $customer, CustomerData $data): bool
    {
        return $customer->name === $data->name
            && $customer->phone === $data->phone
            && self::matchesWhenProvided($customer->email, $data->email)
            && self::matchesWhenProvided($customer->tax_number, $data->tax_number)
            && self::matchesWhenProvided($customer->address, $data->address);
    }

    private static function matchesWhenProvided(?string $stored, ?string $incoming): bool
    {
        return $incoming === null || ($stored ?? '') === $incoming;
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['customer' => $message]);
    }
}
