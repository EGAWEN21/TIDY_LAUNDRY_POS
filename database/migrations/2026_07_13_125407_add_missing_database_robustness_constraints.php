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
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->onDelete('cascade');
            
            $table->index('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('financial_year_id');
            $table->foreign('financial_year_id')->references('id')->on('financial_years')->onDelete('set null');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->index('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('financial_year_id');
            $table->foreign('financial_year_id')->references('id')->on('financial_years')->onDelete('set null');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->index('service_id');
            $table->dropForeign(['service_id']);
            $table->foreign('service_id')->references('id')->on('services')->onDelete('restrict');
        });

        Schema::table('order_addon_details', function (Blueprint $table) {
            $table->index('addon_id');
            $table->dropForeign(['addon_id']);
            $table->foreign('addon_id')->references('id')->on('addons')->onDelete('restrict');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role_id');
            $table->foreign('role_id')->references('id')->on('user_roles')->onDelete('set null');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->foreign('expense_category_id')->references('id')->on('expense_categories');

            $table->dropForeign(['created_by']);
            $table->dropIndex(['created_by']);
            
            $table->dropForeign(['financial_year_id']);
            $table->dropIndex(['financial_year_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['created_by']);
            $table->dropIndex(['created_by']);
            $table->dropForeign(['financial_year_id']);
            $table->dropIndex(['financial_year_id']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->foreign('service_id')->references('id')->on('services');
            $table->dropIndex(['service_id']);
        });

        Schema::table('order_addon_details', function (Blueprint $table) {
            $table->dropForeign(['addon_id']);
            $table->foreign('addon_id')->references('id')->on('addons');
            $table->dropIndex(['addon_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['role_id']);
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
        });
    }
};
