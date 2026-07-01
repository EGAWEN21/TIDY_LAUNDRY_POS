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
            ['name' => 'accept_reject_order', 'display_name' => 'Accept / Reject Orders', 'category' => 'Order Approval', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'bypass_order_approval', 'display_name' => 'Bypass Order Approval', 'category' => 'Order Approval', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'view_all_requests', 'display_name' => 'View All Requests', 'category' => 'Order Approval', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'edit_pending_requests', 'display_name' => 'Edit Pending Requests', 'category' => 'Order Approval', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'delete_order_requests', 'display_name' => 'Delete Order Requests', 'category' => 'Order Approval', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'bypass_approval_under_limit', 'display_name' => 'Bypass Approval (Under Limit)', 'category' => 'Order Approval', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($permissions as $permission) {
            \Illuminate\Support\Facades\DB::table('permissions')->updateOrInsert(
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
        \Illuminate\Support\Facades\DB::table('permissions')->whereIn('name', [
            'accept_reject_order',
            'bypass_order_approval',
            'view_all_requests',
            'edit_pending_requests',
            'delete_order_requests',
            'bypass_approval_under_limit'
        ])->delete();
    }
};
