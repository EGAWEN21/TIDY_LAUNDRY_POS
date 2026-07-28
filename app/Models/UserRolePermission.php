<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRolePermission extends Model
{
    use HasFactory;

    //permission relation
    public function permission(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Permission::class, 'permission_id', 'id');
    }

    //role relation
    public function role(): BelongsTo
    {
        return $this->belongsTo(\App\Models\UserRole::class, 'role_id', 'id');
    }
}
