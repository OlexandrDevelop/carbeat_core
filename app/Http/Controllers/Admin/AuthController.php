<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendSmsCodeRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Http\Services\SmsService;
use App\Http\Services\UserService;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function requestOtp(SendSmsCodeRequest $request, SmsService $smsService): JsonResponse
    {
        $phone = $request->input('phone');

        if (AdminAccess::allowsPhone($phone)) {
            $smsService->generateAndSendCode($phone);
        }

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

        if (! AdminAccess::allows($user)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        Auth::guard('web')->login($user, true);

        return response()->json(['status' => 'ok']);
    }
}
