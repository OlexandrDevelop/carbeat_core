<?php

namespace App\Support;

use App\Models\User;

class AdminAccess
{
    public static function allowsPhone(string $phone): bool
    {
        if (app()->environment(['local', 'testing']) && (bool) config('admin.allow_all_in_local', true)) {
            return true;
        }

        $allowedPhones = collect(config('admin.allowed_phones', []))
            ->map(static fn ($value): string => trim((string) $value))
            ->filter()
            ->values();

        if ($allowedPhones->isEmpty()) {
            return true;
        }

        return $allowedPhones->contains(trim($phone));
    }

    public static function allows(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (app()->environment(['local', 'testing']) && (bool) config('admin.allow_all_in_local', true)) {
            return true;
        }

        $allowedIds = collect(config('admin.allowed_user_ids', []))
            ->map(static fn ($id): int => (int) $id)
            ->filter()
            ->values();
        $allowedEmails = collect(config('admin.allowed_emails', []))
            ->map(static fn ($email): string => mb_strtolower(trim((string) $email)))
            ->filter()
            ->values();
        $allowedPhones = collect(config('admin.allowed_phones', []))
            ->map(static fn ($phone): string => trim((string) $phone))
            ->filter()
            ->values();

        if ($allowedIds->isEmpty() && $allowedEmails->isEmpty() && $allowedPhones->isEmpty()) {
            return false;
        }

        return $allowedIds->contains((int) $user->id)
            || $allowedEmails->contains(mb_strtolower((string) ($user->email ?? '')))
            || $allowedPhones->contains(trim((string) ($user->phone ?? '')));
    }
}
