<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UpdatesPosSyncTimestamp;

class Service extends Model
{
    use HasFactory, UpdatesPosSyncTimestamp;

    protected $fillable = [
        'service_name',
        'icon',
        'is_active'
    ];
}
