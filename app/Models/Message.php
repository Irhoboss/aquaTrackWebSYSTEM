<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['author', 'body', 'kind'];

    /** System post written by the device pipeline, shown in chat as an alert. */
    public static function alert(string $text): self
    {
        return static::create(['author' => 'AquaTrack', 'body' => $text, 'kind' => 'alert']);
    }
}
