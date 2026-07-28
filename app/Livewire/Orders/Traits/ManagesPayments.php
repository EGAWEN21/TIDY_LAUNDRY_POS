<?php

namespace App\Livewire\Orders\Traits;

use Livewire\Attributes\Computed;

trait ManagesPayments
{
    public function add_payment()
    {
        $this->validate([
            'payment_type' => 'required',
            'payment_amount' => 'required|numeric|min:0|lte:'.$this->getPaymentBalance(),
        ]);

        $payment = [
            'amount' => (float)$this->payment_amount,
            'notes' => $this->notes,
            'payment_type' => $this->payment_type,
            'payment_id' => null
        ];
        $this->payment_amount = '';
        $this->notes = '';
        $this->payment_type = 1;
        array_push($this->payments, $payment);
        $this->dispatch(
            'alert',
            ['type' => 'success',  'message' => ' Payment has been created']
        );
    }

    #[Computed()]
    public function currentBalance()
    {
        return $this->getPaymentBalance();
    }

    public function getPaymentBalance()
    {
        $orderBalance = $this->total;
        $paymentsTotal = 0;
        foreach ($this->payments as $payment) {
            $paymentsTotal += $payment['amount'];
        }
        return $orderBalance - $paymentsTotal;
    }

    public function magicFill()
    {
        if ($this->total) {
            $this->paid_amount = $this->total;
        } else {
            $this->paid_amount = 0;
        }
    }

    //remove payment
    public function removePayment($paymentIndex)
    {
        if (isset($this->payments[$paymentIndex]['payment_id'])) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Historical payments cannot be deleted. Please issue a refund/void instead.']);
            return;
        }
        array_splice($this->payments, $paymentIndex, 1);
    }
}
