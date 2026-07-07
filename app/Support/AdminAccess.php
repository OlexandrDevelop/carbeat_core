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

        return User::where('phone', trim($phone))->where('is_admin', true)->exists();
    }

    public static function allows(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (app()->environment(['local', 'testing']) && (bool) config('admin.allow_all_in_local', true)) {
            return true;
        }

        return (bool) $user->is_admin;
    }
}
