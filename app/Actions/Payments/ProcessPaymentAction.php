<?php

namespace App\Actions\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ProcessPaymentAction
{
    /**
     * Safely process a payment or refund for an order.
     * Throws ValidationException if the transaction is mathematically invalid.
     */
    public static function execute(Order $order, float $amountToPay, string $paymentType, ?string $note = null)
    {
        $totalPaid = Payment::where('order_id', $order->id)->sum('received_amount');
        $remainingBalance = round($order->total - $totalPaid, 2);

        // Validation Rules
        if ($remainingBalance < 0) {
            if ($amountToPay > 0) {
                throw ValidationException::withMessages(['payment_error' => 'Cannot accept payment. Store owes a refund of ' . getFormattedCurrency(abs($remainingBalance)) . '. Please enter a negative amount to issue a refund.']);
            }
            if ($amountToPay < $remainingBalance) {
                throw ValidationException::withMessages(['payment_error' => 'Refund amount cannot exceed the owed balance of ' . getFormattedCurrency(abs($remainingBalance)) . '.']);
            }
        } else {
            if ($amountToPay < 0) {
                throw ValidationException::withMessages(['payment_error' => 'Cannot issue a refund. Customer owes a balance of ' . getFormattedCurrency($remainingBalance) . '.']);
            }
            if ($amountToPay > $remainingBalance) {
                throw ValidationException::withMessages(['payment_error' => 'Paid Amount cannot be greater than remaining balance (' . getFormattedCurrency($remainingBalance) . ').']);
            }
        }

        if ($order->status == 4 && $amountToPay > 0) {
            throw ValidationException::withMessages(['payment_error' => 'Cannot accept payment for a Returned/Voided order.']);
        }

        // Create Payment
        $payment = Payment::create([
            'payment_date'  => Carbon::today()->toDateString(),
            'customer_id'   => $order->customer_id,
            'customer_name' => $order->customer_name,
            'order_id'      => $order->id,
            'payment_type'  => $paymentType,
            'payment_note'  => $note,
            'financial_year_id' => resolveFinancialYearId(Carbon::today()->toDateString()),
            'received_amount'   => $amountToPay,
            'created_by'    => Auth::id(),
        ]);

        return $payment;
    }
}
