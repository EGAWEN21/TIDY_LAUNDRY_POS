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
        $reports = ['report_customer', 'report_insights'];
        
        foreach ($reports as $r) {
            \Illuminate\Support\Facades\DB::table('permissions')->updateOrInsert(
                ['name' => $r],
                [
                    'display_name' => ucwords(str_replace('_', ' ', $r)),
                    'guard_name' => 'web',
                    'category' => 'Report',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
