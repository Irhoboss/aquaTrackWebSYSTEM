<?php

namespace App\Support;

use App\Models\AuditLog;

class Audit
{
    private const HIDDEN = ['password', 'password_confirmation', 'current_password', 'token', 'secret', '_token', 'new_token'];

    public static function log(string $action, $subject = null, array $details = [], $actor = null): void
    {
        try {
            $user = $actor ?? auth()->user();
            $clean = collect($details)->except(self::HIDDEN)->all();
            AuditLog::create([
                'user_id' => $user?->id,
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->id,
                'subject_label' => $subject ? ($subject->name ?? $subject->title ?? $subject->code ?? $subject->sku ?? $subject->device_id ?? null) : null,
                'details' => $clean ?: null,
                'ip' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e); // auditing must never break the user's action
        }
    }
}
