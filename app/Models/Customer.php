<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\UpdatesPosSyncTimestamp;

class Customer extends Model
{
    use HasFactory, UpdatesPosSyncTimestamp, SoftDeletes;
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
