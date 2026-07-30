<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\DTOs\OrderData;

$data = [
    'delivery_date'=>'2026-07-30',
    'sub_total'=>1000,
    'addon_total'=>0,
    'discount'=>0,
    'tax_percentage'=>0,
    'tax_amount'=>0,
    'tax_type'=>1,
    'taxable_amount'=>0,
    'total'=>1000,
    'details'=>[],
    'payments'=>[],
    'status'=>0
];
try {
    $dto = OrderData::from($data);
    var_dump($dto);
} catch (\Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage();
}
