<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendSmsCodeRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Services\SmsService;
use App\Http\Services\TokenService;
use App\Http\Services\UserService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function requestOtp(SendSmsCodeRequest $request, SmsService $smsService): JsonResponse
    {
        $phone = $request->input('phone');
        $appHash = $request->input('app_hash'); // optional Android SMS Retriever app signature
        $smsService->generateAndSendCode($phone, 4, $appHash);

        // Check whether user+master already exist
        $needsRegistration = true;
        $user = \App\Models\User::where('phone', $phone)->first();
        if ($user && $user->master) {
            $needsRegistration = false;
        }

        return response()->json([
            'message' => 'OTP sent',
            'needs_registration' => $needsRegistration,
        ], 200);
    }

    public function verifyOtp(VerifyCodeRequest $request, SmsService $smsService, UserService $userService, TokenService $tokenService): JsonResponse
    {
        $data = $request->validated();

        if (! $smsService->verifyCode($data['phone'], $data['sms_code'])) {
            return response()->json(['error' => 'Wrong code'], 400);
        }

        // Find or create user
        $user = $userService->findUserByPhone($data['phone']);
        if (! $user) {
            // Auto-create user with phone only
            $user = User::create([
                'phone' => $data['phone'],
                'name' => 'Master '.substr($data['phone'], -4),
            ]);
        }

        // If there is a master with this contact phone but without user — attach
        $userService->attachUserToMasterByPhone($data['phone'], $user);

        // Mark phone verified
        if (is_null($user->phone_verified_at)) {
            $user->phone_verified_at = now();
            $user->save();
        }

        $access = $tokenService->createAccessToken($user);
        $refreshModel = $tokenService->createRefreshToken($user);

        return response()->json([
            'user' => new UserResource($user),
            'access_token' => $access,
            'refresh_token' => $refreshModel->plain_token,
            'expires_in' => 60 * config('auth.access_token_ttl', 15),
        ]);
    }

    public function refresh(Request $request, TokenService $tokenService): JsonResponse
    {
        $plainRefresh = $request->input('refresh_token');
        if (! $plainRefresh) {
            return response()->json(['error' => 'refresh_token_required'], 422);
        }

        $refreshModel = $tokenService->validateRefreshToken($plainRefresh);
        if (! $refreshModel) {
            return response()->json(['error' => 'invalid_refresh_token'], 401);
        }

        // Rotate token
        /** @var User|null $user */
        $user = $refreshModel->user;
        if (! $user instanceof User) {
            return response()->json(['error' => 'invalid_refresh_token'], 401);
        }
        // Optionally revoke current and issue new
        $tokenService->revoke($refreshModel);
        $newRefresh = $tokenService->createRefreshToken($user);
        $access = $tokenService->createAccessToken($user);

        return response()->json([
            'access_token' => $access,

            'refresh_token' => $newRefresh->plain_token,
            'expires_in' => 60 * config('auth.access_token_ttl', 15),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => new UserResource($request->user())]);
    }

    public function logout(Request $request, TokenService $tokenService): JsonResponse
    {
        $plainRefresh = $request->input('refresh_token');

        if ($plainRefresh) {
            $refreshModel = $tokenService->validateRefreshToken($plainRefresh);
            if ($refreshModel) {
                $tokenService->revoke($refreshModel);
            }
        }

        // Also revoke all user's refresh tokens if needed
        $user = $request->user();
        if ($user) {
            $user->refreshTokens()->update(['revoked' => true]);
        }

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}
