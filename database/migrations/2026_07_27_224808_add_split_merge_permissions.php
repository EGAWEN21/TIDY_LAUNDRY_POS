<?php

use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            ['name' => 'order_merge', 'display_name' => 'Merge Orders', 'category' => 'Order'],
            ['name' => 'order_split', 'display_name' => 'Split Orders', 'category' => 'Order'],
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\Permission::whereIn('name', ['order_merge', 'order_split'])->delete();
    }
};
