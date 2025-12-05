<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Claim\ClaimSendSmsRequest;
use App\Http\Requests\Claim\ClaimVerifyRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Services\TokenService;
use App\Models\Master;
use App\Models\User;
use Carbon\Carbon;
use Daaner\TurboSMS\Facades\TurboSMS;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class ClaimController extends Controller
{
    public function publicInfo(string $token): JsonResponse
    {
        $master = Master::where('claim_token', $token)->firstOrFail();

        return response()->json([
            'status' => 'ok',
            'master_id' => $master->id,
            'is_claimed' => (bool) $master->is_claimed,
            'master_phone' => $master->phone,
        ]);
    }

    public function sendSms(ClaimSendSmsRequest $request, PhoneHelper $phoneHelper): JsonResponse
    {
        $data = $request->validated();
        $master = Master::findOrFail($data['master_id']);

        if ($master->is_claimed) {
            return response()->json([
                'status' => 'already_claimed',
                'message' => 'Master profile already claimed',
            ], 409);
        }

        if (empty($master->claim_token)) {
            $master->claim_token = Str::random(40);
            $master->save();
        }

        $normalizedPhone = $phoneHelper->normalize($data['phone']);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $payload = json_encode([
            'code' => $code,
            'phone' => $normalizedPhone,
        ]);

        $redisKey = $this->buildRedisKey($master->id);
        Redis::setex($redisKey, 5 * 60, $payload);

        $this->sendClaimMessage($normalizedPhone, $master, $code);

        return response()->json(['status' => 'sent']);
    }

    public function verify(
        ClaimVerifyRequest $request,
        PhoneHelper $phoneHelper,
        TokenService $tokenService
    ): JsonResponse {
        $data = $request->validated();
        $master = Master::findOrFail($data['master_id']);

        $redisKey = $this->buildRedisKey($master->id);
        $cached = Redis::get($redisKey);

        if (! $cached) {
            return response()->json(['error' => 'code_expired'], 410);
        }

        $cachedData = json_decode($cached, true);
        $normalizedPhone = $phoneHelper->normalize($data['phone']);

        if (! $cachedData || ! isset($cachedData['code']) || $cachedData['code'] !== $data['code']) {
            return response()->json(['error' => 'invalid_code'], 422);
        }

        if (! isset($cachedData['phone']) || $cachedData['phone'] !== $normalizedPhone) {
            return response()->json(['error' => 'phone_mismatch'], 422);
        }

        $master->is_claimed = true;
        $master->phone_verified_at = Carbon::now();
        $master->claim_token = null;
        $master->contact_phone = $normalizedPhone;
        $master->save();

        /** @var User $user */
        $user = $master->user;
        if (! $user) {
            $user = User::firstOrCreate(
                ['phone' => $normalizedPhone],
                ['name' => $master->name]
            );
            $master->user()->associate($user);
            $master->save();
        } else {
            if ($user->phone !== $normalizedPhone) {
                $user->phone = $normalizedPhone;
            }
            if (empty($user->name)) {
                $user->name = $master->name;
            }
        }

        if (is_null($user->phone_verified_at)) {
            $user->phone_verified_at = Carbon::now();
        }
        $user->save();

        Redis::del($redisKey);

        $accessToken = $tokenService->createAccessToken($user);
        $refreshModel = $tokenService->createRefreshToken($user);
        $expiresIn = 60 * config('auth.access_token_ttl', 15);

        return response()->json([
            'status' => 'verified',
            'user' => new UserResource($user->fresh('master')),
            'access_token' => $accessToken,
            'refresh_token' => $refreshModel->plain_token,
            'expires_in' => $expiresIn,
        ]);
    }

    private function sendClaimMessage(string $phone, Master $master, string $code): void
    {
        $base = rtrim(config('app.claim_base_url'), '/');
        $token = $master->claim_token;

        if (! $token) {
            return;
        }

        $link = "{$base}/{$token}?master_id={$master->id}";
        $message = "Ваш профіль автомайстра додано в Carbeat.\n"
            ."Підтвердіть, що це Ви: {$link}\n"
            ."Код підтвердження: {$code}";

        try {
            TurboSMS::sendMessages($phone, $message);
        } catch (\Throwable $e) {
            Log::error('Failed to send claim SMS', [
                'master_id' => $master->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function buildRedisKey(int $masterId): string
    {
        return "claim_sms:{$masterId}";
    }
}

