<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UpdatesPosSyncTimestamp;

class ServiceType extends Model
{
    use HasFactory, UpdatesPosSyncTimestamp;
    protected $fillable = [
        'service_type_name',
        'is_active',
        'position'
    ];
}
