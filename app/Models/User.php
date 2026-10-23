<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    protected $fillable = ['name', 'username', 'email', 'phone', 'role', 'is_active', 'must_change_password', 'avatar', 'password'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public const ROLES = ['owner' => 'Owner / Administrator', 'manager' => 'Manager', 'staff' => 'Staff / Employee', 'customer' => 'Customer'];

    public function inbox(): HasMany { return $this->hasMany(UserNotification::class); }
    public function auditLogs(): HasMany { return $this->hasMany(AuditLog::class); }

    public function getRoleLabelAttribute(): string { return self::ROLES[$this->role] ?? $this->role; }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            return Storage::disk('public')->url($this->avatar);
        }
        return null;
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', trim($this->name)))->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    }
}
