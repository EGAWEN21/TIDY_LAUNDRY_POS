<?php

$payload = [
    'orders' => [
        [
            'uuid' => 'simulated-'.time(), 
            'customer_id' => 1, 
            'customer_name' => 'John Doe', 
            'phone_number' => '1234567890', 
            'order_date' => now()->toDateTimeString(), 
            'delivery_date' => now()->toDateTimeString(), 
            'sub_total' => 100, 
            'addon_total' => 0, 
            'discount' => 0, 
            'tax_percentage' => 0, 
            'tax_amount' => 0, 
            'tax_type' => 'inclusive', 
            'taxable_amount' => 100, 
            'total' => 100, 
            'details' => [
                [
                    'service_id' => 1, 
                    'service_name' => 'Service 1', 
                    'color_code' => '#000',
                    'service_type_ids' => [1],
                    'service_price' => 100, 
                    'service_quantity' => 1, 
                    'service_detail_total' => 100
                ]
            ], 
            'payments' => [
                [
                    'amount' => 100, 
                    'payment_type_name' => 'cash',
                    'payment_type' => 1,
                    'payment_id' => 1,
                    'notes' => 'test'
                ]
            ]
        ]
    ]
];

$res = \App\Actions\Orders\SyncOfflineOrdersAction::execute($payload['orders'], \App\Models\User::find(1)); 

echo json_encode([
    'res' => $res, 
    'order' => \App\Models\Order::where('uuid', $payload['orders'][0]['uuid'])->first()
]);
