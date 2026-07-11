<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UpdatesPosSyncTimestamp;

class Payment extends Model
{
    // Updates the 'pos_last_update' cache timestamp when a payment is created/modified,
    // ensuring the Offline POS app instantly syncs updated customer balances.
    use HasFactory, UpdatesPosSyncTimestamp;
    protected $fillable = [
        'payment_date',
        'customer_id',
        'customer_name',
        'order_id',
        'received_amount',
        'payment_type',
        'payment_note',
        'financial_year_id',
        'created_by'
    ];

     /* customer relation */
     public function customer()
     {
         return $this->belongsTo(\App\Models\Customer::class, 'customer_id', 'id');
     }
      /* order relation */
      public function order()
      {
          return $this->belongsTo(\App\Models\Order::class, 'order_id', 'id');
      }
}
