<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Payments\PaymentsReceiptView;
use App\Livewire\Reports\CustomerReport;
use App\Livewire\Reports\OrderReport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentReportAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_staff_without_payment_list_permission_cannot_view_payment_receipts(): void
    {
        $user = $this->createStaff('payment-denied');

        Livewire::actingAs($user)
            ->test(PaymentsReceiptView::class)
            ->assertStatus(404);
    }

    public function test_super_admin_can_view_payment_receipts(): void
    {
        Livewire::actingAs(User::firstOrFail())
            ->test(PaymentsReceiptView::class)
            ->assertStatus(200);
    }

    public function test_staff_without_order_report_permission_cannot_view_order_reports(): void
    {
        $user = $this->createStaff('order-report-denied');

        Livewire::actingAs($user)
            ->test(OrderReport::class)
            ->assertStatus(404);
    }

    public function test_super_admin_can_view_order_reports(): void
    {
        Livewire::actingAs(User::firstOrFail())
            ->test(OrderReport::class)
            ->assertStatus(200)
            ->assertSet('status', -1);
    }

    public function test_staff_without_customer_report_permission_cannot_view_customer_reports(): void
    {
        $user = $this->createStaff('customer-report-denied');

        Livewire::actingAs($user)
            ->test(CustomerReport::class)
            ->assertStatus(404);
    }

    public function test_super_admin_can_view_customer_reports(): void
    {
        Livewire::actingAs(User::firstOrFail())
            ->test(CustomerReport::class)
            ->assertStatus(200)
            ->assertSet('statusFilter', 'all');
    }

    private function createStaff(string $suffix): User
    {
        return User::create([
            'name' => 'Unauthorized Staff',
            'email' => $suffix . '-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_type' => 2,
            'is_active' => 1,
        ]);
    }
}
