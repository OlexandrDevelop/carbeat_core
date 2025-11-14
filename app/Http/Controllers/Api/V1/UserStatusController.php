<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserStatusResource;
use App\Models\Master;
use Illuminate\Support\Facades\Auth;

class UserStatusController extends Controller
{
    public function status(): UserStatusResource
    {
        $user = Auth::guard('api')->user();
        $master = $user ? Master::where('user_id', $user->id)->first() : null;

        $isPremium = false;
        $premiumUntilIso = null;
        if ($master) {
            $isPremium = (bool) $master->is_premium;
            if ($master->premium_until && $master->premium_until->isFuture()) {
                $isPremium = true;
            }
            $premiumUntilIso = $master->premium_until?->toIso8601String();
        }

        $data = [
            'is_premium' => $isPremium,
            'premium_until' => $premiumUntilIso,
            'max_photos' => $isPremium ? (int) config('limits.max_photos_premium') : (int) config('limits.max_photos_free'),
            'max_description' => $isPremium ? (int) config('limits.max_description_premium') : (int) config('limits.max_description_free'),
            'max_services' => $isPremium ? (int) config('limits.max_services_premium') : (int) config('limits.max_services_free'),
        ];

        return new UserStatusResource($data);
    }
}


