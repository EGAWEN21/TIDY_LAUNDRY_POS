<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'user_type',
        'role_id',
        'is_active',
        'current_session_id',
        'viewable_staff_orders',
        'viewable_staff_customers',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if the user has a specific permission.
     * Admin users (user_type == 1) bypass all permission checks.
     *
     * @param string $permission The permission name to check (e.g., 'order_create')
     * @return bool
     */
    public function hasPermission($permission)
    {
        if ($this->user_type == 1) {
            return true;
        }
        if (!$this->role) {
            return false;
        }
        return $this->role->permissions()->where('permission_name', $permission)->exists();
    }

    /**
     * Get all permissions through the user's role.
     */
    public function permissions()
    {
        return $this->role ? $this->role->permissions() : collect();
    }

    //roles relation
    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'role_id');
    }

    public function getViewableCustomerUserIds()
    {
        if ($this->user_type == 1 || $this->viewable_staff_customers === 'all') {
            return 'all';
        }
        $viewable_ids = [$this->id];
        if (!empty($this->viewable_staff_customers)) {
            $extra_ids = explode(',', $this->viewable_staff_customers);
            $viewable_ids = array_merge($viewable_ids, $extra_ids);
        }
        return $viewable_ids;
    }
}
