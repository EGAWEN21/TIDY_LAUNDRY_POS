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
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->softDeletes();
        });

        $permissions = [
            ['name' => 'customer_force_delete', 'display_name' => 'Customer Force Delete', 'category' => 'Customer'],
            ['name' => 'customer_restore', 'display_name' => 'Customer Restore', 'category' => 'Customer'],
            ['name' => 'user_force_delete', 'display_name' => 'User Force Delete', 'category' => 'User'],
            ['name' => 'user_restore', 'display_name' => 'User Restore', 'category' => 'User'],
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::updateOrCreate(
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        \App\Models\Permission::whereIn('name', [
            'customer_force_delete',
            'customer_restore',
            'user_force_delete',
            'user_restore'
        ])->delete();
    }
};
