<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthOtp extends Model
{
    protected $fillable = [
        'user_id', 'email', 'purpose', 'code_hash', 'payload',
        'attempts', 'expires_at', 'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
