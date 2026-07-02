<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UpdatesPosSyncTimestamp;

class ServiceDetail extends Model
{
    use HasFactory, UpdatesPosSyncTimestamp;
    protected $fillable = [
        'service_id',
        'service_type_id',
        'service_price'
    ];
}
