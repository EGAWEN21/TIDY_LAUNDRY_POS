<?php

use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $reports = [
            'report_customer' => 'Customer Report',
            'report_insights' => 'Business Insights',
        ];

        foreach ($reports as $name => $displayName) {
            \Illuminate\Support\Facades\DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'display_name' => $displayName,
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
