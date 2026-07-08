<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendSmsCodeRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Http\Services\SmsService;
use App\Http\Services\UserService;
use App\Models\Master;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Self-service web auth for masters (garage owners/technicians), fully
 * separate from the platform-admin auth in App\Http\Controllers\Admin\AuthController.
 * Same OTP mechanics, session guard, but a different access check: a master
 * must already have a `masters` row linked (by phone) to their user, since a
 * web login never creates a new Master record — that stays a mobile/admin flow.
 */
class AuthController extends Controller
{
    public function requestOtp(SendSmsCodeRequest $request, SmsService $smsService): JsonResponse
    {
        $phone = $request->input('phone');

        if (Master::where('contact_phone', $phone)->exists()) {
            $smsService->generateAndSendCode($phone);
        }

        // Always respond the same way regardless of whether the phone belongs
        // to a master, so this endpoint can't be used to enumerate masters.
        return response()->json(['message' => 'OTP sent']);
    }

    public function verifyOtp(VerifyCodeRequest $request, SmsService $smsService, UserService $userService): JsonResponse
    {
        $data = $request->validated();

        if (! $smsService->verifyCode($data['phone'], $data['sms_code'])) {
            return response()->json(['error' => 'Wrong code'], 400);
        }

        $user = $userService->findUserByPhone($data['phone']);
        if (! $user) {
            $user = User::create([
                'phone' => $data['phone'],
                'name' => 'User '.substr($data['phone'], -4),
            ]);
        }
        $userService->attachUserToMasterByPhone($data['phone'], $user);

        if (is_null($user->phone_verified_at)) {
            $user->phone_verified_at = now();
        }
        $user->last_login_at = now();
        $user->save();

        if (! Master::where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'This phone number is not registered as a master.'], 403);
        }

        Auth::guard('web')->login($user, true);

        return response()->json(['status' => 'ok']);
    }
}
