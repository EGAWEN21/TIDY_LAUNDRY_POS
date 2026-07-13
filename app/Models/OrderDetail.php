<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'order_id',
        'service_id',
        'service_name',
        'service_price',
        'service_quantity',
        'service_detail_total',
        'color_code'
    ];

    public function service()
    {
        return $this->belongsTo(\App\Models\Service::class, 'service_id', 'id');
    }
}
