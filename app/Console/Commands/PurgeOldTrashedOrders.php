<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderAddonDetail;
use App\Models\Payment;

class PurgeOldTrashedOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:purge-trashed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete orders that have been in the recycle bin for more than 90 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orders = Order::onlyTrashed()->where('deleted_at', '<', now()->subDays(90))->get();
        $count = 0;

        foreach ($orders as $order) {
            OrderDetail::onlyTrashed()->where('order_id', $order->id)->forceDelete();
            OrderAddonDetail::onlyTrashed()->where('order_id', $order->id)->forceDelete();
            Payment::onlyTrashed()->where('order_id', $order->id)->forceDelete();
            $order->forceDelete();
            $count++;
        }

        $this->info("Successfully purged {$count} old trashed orders.");
    }
}
