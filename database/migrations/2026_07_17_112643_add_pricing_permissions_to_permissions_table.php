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
        $permissions = [
            ['name' => 'order_price_override', 'display_name' => 'Edit Service/Addon Prices', 'category' => 'Order'],
            ['name' => 'order_discount_apply', 'display_name' => 'Apply Custom Discounts', 'category' => 'Order'],
        ];

        foreach ($permissions as $permission) {
            $id = \Illuminate\Support\Facades\DB::table('permissions')->insertGetId([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'category' => $permission['category'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign to Super Admin role if it exists (role_id 1)
            $superAdmin = \Illuminate\Support\Facades\DB::table('user_roles')->where('id', 1)->first();
            if ($superAdmin) {
                \Illuminate\Support\Facades\DB::table('user_role_permissions')->insert([
                    'role_id' => 1,
                    'permission_id' => $id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = ['order_price_override', 'order_discount_apply'];
        $ids = \Illuminate\Support\Facades\DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
        
        \Illuminate\Support\Facades\DB::table('user_role_permissions')->whereIn('permission_id', $ids)->delete();
        \Illuminate\Support\Facades\DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
