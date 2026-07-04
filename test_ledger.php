<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $paymentsSub = Illuminate\Support\Facades\DB::table('payments')
        ->select('order_id', Illuminate\Support\Facades\DB::raw('SUM(received_amount) as total_paid'))
        ->groupBy('order_id');

    $orders = Illuminate\Support\Facades\DB::table('orders')
        ->leftJoinSub($paymentsSub, 'paid_orders', function ($join) {
            $join->on('orders.id', '=', 'paid_orders.order_id');
        })
        ->select('orders.id', 'orders.order_date', 'orders.total', Illuminate\Support\Facades\DB::raw('COALESCE(paid_orders.total_paid, 0) as paid'))
        ->whereRaw('orders.total > COALESCE(paid_orders.total_paid, 0)')
        ->get();
        
    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
