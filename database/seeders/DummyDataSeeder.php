<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\ServiceDetail;
use App\Models\Customer;

class DummyDataSeeder extends Seeder
{
    public function run()
    {
        $service = Service::create([
            'service_name' => 'Laundry Wash',
            'is_active' => 1,
            'icon' => 'laundry.png'
        ]);

        $service2 = Service::create([
            'service_name' => 'Dry Cleaning',
            'is_active' => 1,
            'icon' => 'dryclean.png'
        ]);

        $type1 = ServiceType::create([
            'service_type_name' => 'Standard',
            'is_active' => 1,
            'position' => 1
        ]);

        $type2 = ServiceType::create([
            'service_type_name' => 'Premium (Same Day)',
            'is_active' => 1,
            'position' => 2
        ]);

        ServiceDetail::create([
            'service_id' => $service->id,
            'service_type_id' => $type1->id,
            'service_price' => 50.0
        ]);

        ServiceDetail::create([
            'service_id' => $service->id,
            'service_type_id' => $type2->id,
            'service_price' => 100.0
        ]);
        
        ServiceDetail::create([
            'service_id' => $service2->id,
            'service_type_id' => $type1->id,
            'service_price' => 200.0
        ]);

        Customer::create([
            'name' => 'Test Customer',
            'phone' => '1234567890',
            'email' => 'test@example.com',
            'is_active' => 1
        ]);
        
        Customer::create([
            'name' => 'Walk-in User',
            'phone' => '0000000000',
            'is_active' => 1
        ]);
    }
}
