<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UpdatesPosSyncTimestamp;

class Addon extends Model
{
    use HasFactory, UpdatesPosSyncTimestamp;
    protected $fillable = [
        'addon_name',
        'addon_price',
        'is_active',
    ];
}
