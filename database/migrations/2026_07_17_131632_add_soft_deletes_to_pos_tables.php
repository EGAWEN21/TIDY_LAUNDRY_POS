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
        $tables = ['orders', 'order_addon_details', 'order_details', 'payments', 'services'];
        foreach ($tables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table) && !\Illuminate\Support\Facades\Schema::hasColumn($table, 'deleted_at')) {
                \Illuminate\Support\Facades\Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This compatibility migration only adds missing columns. It must not
        // remove soft-delete columns owned by earlier table-specific migrations.
    }
};
