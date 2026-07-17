<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'request_number',
        'created_by',
        'customer_id',
        'customer_name',
        'total_amount',
        'payload',
        'status',
        'rejection_note',
        'uuid',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->request_number)) {
                $sequence = \Illuminate\Support\Facades\DB::table('sequences')
                    ->where('name', 'request_number')->lockForUpdate()->first();
                $new_code = $sequence->value + 1;
                \Illuminate\Support\Facades\DB::table('sequences')
                    ->where('name', 'request_number')->update(['value' => $new_code]);
                $model->request_number = 'REQ-' . str_pad($new_code, 4, "0", STR_PAD_LEFT);
            }
        });
    }
}
