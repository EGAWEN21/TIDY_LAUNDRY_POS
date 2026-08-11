<?php

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $viewableIds;

    public function __construct($viewableIds = null)
    {
        $this->viewableIds = $viewableIds;
    }

    public function query()
    {
        $query = Customer::query();

        if ($this->viewableIds !== 'all' && $this->viewableIds !== null) {
            $query->whereIn('created_by', $this->viewableIds);
        } elseif ($this->viewableIds === null) {
            // Fallback if instantiated without args, apply current auth user rules
            if (Auth::check()) {
                $viewable = Auth::user()->getViewableCustomerUserIds();
                if ($viewable !== 'all') {
                    $query->whereIn('created_by', $viewable);
                }
            }
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Phone',
            'Email',
            'Tax Number',
            'Address',
            'Active',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->name,
            $customer->phone,
            $customer->email,
            $customer->tax_number,
            $customer->address,
            $customer->is_active ? 'Yes' : 'No',
        ];
    }
}
