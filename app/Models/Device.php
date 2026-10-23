<?php

namespace App\Models;

use App\Support\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Device extends Model
{
    protected $fillable = ['device_id', 'name', 'location', 'token_hash', 'is_active', 'last_seen_at', 'last_ip', 'firmware'];
    protected $hidden = ['token_hash'];
    protected $casts = ['is_active' => 'boolean', 'last_seen_at' => 'datetime'];

    public function readings(): HasMany { return $this->hasMany(Reading::class); }

    public function isOnline(): bool
    {
        return $this->is_active && $this->last_seen_at
            && $this->last_seen_at->gte(now()->subMinutes((int) Setting::get('offline_minutes')));
    }

    /** Creates a new random token, stores only its hash, and returns the plain token (show it once). */
    public function issueToken(): string
    {
        $plain = Str::random(48);
        $this->forceFill(['token_hash' => hash('sha256', $plain), 'is_active' => true])->save();
        return $plain;
    }
}
