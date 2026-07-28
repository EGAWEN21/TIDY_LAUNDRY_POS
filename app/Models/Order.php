<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\UpdatesPosSyncTimestamp;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    // Updates the 'pos_last_update' cache timestamp when an order is created/modified,
    // ensuring the Offline POS app instantly syncs updated customer balances.
    use HasFactory;
    use UpdatesPosSyncTimestamp;
    use SoftDeletes;
    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_name',
        'phone_number',
        'order_date',
        'delivery_date',
        'sub_total',
        'addon_total',
        'discount',
        'tax_percentage',
        'tax_amount',
        'tax_type',
        'taxable_amount',
        'total',
        'note',
        'status',
        'order_type',
        'created_by',
        'financial_year_id',
        'uuid'
    ];

    /* user relation */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'id')->withTrashed();
    }

    /* user relation */
    public function details(): HasMany
    {
        return $this->hasMany(\App\Models\OrderDetail::class, 'order_id', 'id');
    }

    /* addon relation */
    public function addons(): HasMany
    {
        return $this->hasMany(\App\Models\OrderAddonDetail::class, 'order_id', 'id');
    }

    /* deleted by relation */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by', 'id');
    }

    /* payments relation */
    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\Payment::class, 'order_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id', 'id')->withTrashed();
    }

    protected static function booted()
    {
        static::updating(function ($order) {
            if ($order->isDirty('status')) {
                $oldStatus = $order->getOriginal('status');
                $newStatus = $order->status;

                // Prevent moving backwards from Delivered (3) to Pending/Processing/Ready (0, 1, 2)
                if ($oldStatus == 3 && in_array($newStatus, [0, 1, 2])) {
                    throw new \DomainException("Cannot transition order status backwards from Delivered.");
                }

                // Prevent changes if already Returned (4)
                if ($oldStatus == 4 && $newStatus != 4) {
                    throw new \DomainException("Cannot change status of a returned or cancelled order.");
                }
            }
        });
    }
}
