<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UpdatesPosSyncTimestamp;

class Customer extends Model
{
    use HasFactory, UpdatesPosSyncTimestamp;
    protected $fillable = [
        'name',
        'email',
        'phone',
        'tax_number',
        'address',
        'is_active',
        'created_by'
    ];
}
