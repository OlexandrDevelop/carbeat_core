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
        Master::where('contact_phone', $phone)
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', 1)
                    ->orWhere('user_id', $user->id);
            })
            ->update(['user_id' => $user->id]);
    }
}
