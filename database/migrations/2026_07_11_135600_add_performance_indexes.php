<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('customer_id');
            $table->index('status');
            $table->index('order_number');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('phone');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['order_number']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['phone']);
        });
    }
}
