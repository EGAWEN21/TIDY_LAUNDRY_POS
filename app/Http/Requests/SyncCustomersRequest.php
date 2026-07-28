<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth is handled by Sanctum middleware
    }

    public function rules(): array
    {
        return [
            'customers' => 'required|array|max:50',
            'customers.*.name' => 'required|string|max:255',
            'customers.*.phone' => 'required|string|max:20',
            'customers.*.email' => 'nullable|email|max:255',
            'customers.*.uuid' => 'nullable|string|uuid|max:36',
            'customers.*.tax_number' => 'nullable|string|max:50',
            'customers.*.address' => 'nullable|string|max:500',
        ];
    }
}
