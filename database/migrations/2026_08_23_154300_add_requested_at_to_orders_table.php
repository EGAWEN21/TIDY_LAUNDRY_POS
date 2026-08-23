<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a nullable `requested_at` column to the `orders` table.
     * When an order originates from an accepted order request (REQ-xxxx),
     * this column stores the original request creation timestamp for audit trail.
     * For direct orders (bypass approval), this remains null.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('requested_at')->nullable()->after('order_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('requested_at');
        });
    }
};
