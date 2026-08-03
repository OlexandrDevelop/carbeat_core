<?php

namespace App\Http\Services;

use App\Models\Master;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserService
{
    public function createOrUpdateFromMaster(Master $master)
    {
        $user = User::updateOrCreate(
            [
                'phone' => $master->phone,
            ],
            ['name' => $master->name]
        );

        $master->user()->associate($user);
        $master->save();

        return $user;
    }

    public function createOrUpdateForClient(array $data)
    {
        // Ensure we gracefully handle concurrent attempts that may create the same phone record
        try {
            return User::updateOrCreate(
                ['phone' => $data['phone']],
                ['name' => $data['name']]
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // A record for this phone already exists – retrieve and update it instead
            $user = User::where('phone', $data['phone'])->first();
            if ($user) {
                $user->update(['name' => $data['name']]);

                return $user;
            }

            // Re-throw if, for some reason, the user still does not exist
            throw $e;
        }
    }

    public function findUserByPhone(string $phone)
    {
        return User::where('phone', $phone)->first();
    }

    public function createTokenForUser($user)
    {
        try {
            return $token = JWTAuth::claims(['phone' => $user->phone])->fromUser($user);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function attachUserToMasterByPhone(string $phone, User $user): void
    {
        // Multiple master rows can share the same contact_phone (duplicate
        // imports, several branches, etc.), and `user_id` is unique. A bulk
        // UPDATE across all matches would try to set that unique column to
        // the same value on more than one row and blow up with a
        // UniqueConstraintViolationException. Pick a single best match and
        // update only that row instead.
        $candidates = Master::where('contact_phone', $phone)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id')
                    ->orWhere('user_id', 1);
            })
            ->get();

        if ($candidates->isEmpty()) {
            return;
        }

        $master = $candidates->first(fn ($m) => (int) $m->user_id === $user->id)
            ?? $candidates->whereNull('user_id')->sortBy('id')->first()
            ?? $candidates->sortBy('id')->first();

        if ((int) $master->user_id === $user->id) {
            return;
        }

        try {
            $master->update(['user_id' => $user->id]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Another master row already holds this user_id (a stale
            // system-placeholder collision) — leave this row unlinked
            // rather than failing the whole login.
        }
    }
}
