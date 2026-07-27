<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            ['name' => 'order_void', 'display_name' => 'Order Void', 'category' => 'Order'],
            ['name' => 'payment_void', 'display_name' => 'Payment Void', 'category' => 'Payment'],
            ['name' => 'payment_refund', 'display_name' => 'Payment Refund', 'category' => 'Payment'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
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
        Permission::whereIn('name', ['order_void', 'payment_void', 'payment_refund'])->delete();
    }
};
