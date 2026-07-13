<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique();
        });

        Schema::table('order_requests', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique();
        });

        Schema::create('sequences', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->bigInteger('value');
        });

        // Insert initial values for sequences
        DB::table('sequences')->insert([
            ['name' => 'order_number', 'value' => 0],
            ['name' => 'request_number', 'value' => 0],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequences');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('order_requests', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
