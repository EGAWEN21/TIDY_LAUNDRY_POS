<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Actions\Orders\SyncOfflineOrdersAction;
use App\Models\User;

$user = User::first();

$payload = [
    [
        'uuid' => 'OFFLINE-123456',
        'type' => 'order',
        'data' => [
            'uuid' => 'OFFLINE-123456',
            'order_date' => '2026-07-28',
            'delivery_date' => '2026-07-30',
            'sub_total' => 1000,
            'addon_total' => 0,
            'discount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'tax_type' => 1,
            'taxable_amount' => 0,
            'total' => 1000,
            'details' => [
                [
                    'service_id' => 1,
                    'service_name' => 'Wash & Iron',
                    'service_type_ids' => [1],
                    'service_quantity' => 1,
                    'service_detail_total' => 1000,
                    'service_price' => 1000,
                    'color_code' => ''
                ]
            ],
            'payments' => [],
            'status' => 0
        ]
    ]
];

$result = SyncOfflineOrdersAction::execute($payload, $user);
echo json_encode($result, JSON_PRETTY_PRINT);
