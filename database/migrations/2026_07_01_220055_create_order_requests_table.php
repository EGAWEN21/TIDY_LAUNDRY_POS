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
        Schema::create('order_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->longText('payload');
            $table->integer('status')->default(0); // 0: Pending, 1: Rejected
            $table->text('rejection_note')->nullable();
            $table->timestamps();
        });

        // Seed new permissions
        $permissions = [
            [
                'name' => 'accept_reject_order',
                'category' => 'Order',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'bypass_order_approval',
                'category' => 'Order',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        // Insert if they don't already exist to avoid errors
        foreach ($permissions as $permission) {
            if (!\Illuminate\Support\Facades\DB::table('permissions')->where('name', $permission['name'])->exists()) {
                \Illuminate\Support\Facades\DB::table('permissions')->insert($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_requests');
    }
};
