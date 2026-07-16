<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UpdatesPosSyncTimestamp;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, UpdatesPosSyncTimestamp, SoftDeletes;

    protected $fillable = [
        'service_name',
        'icon',
        'is_active'
    ];
}
