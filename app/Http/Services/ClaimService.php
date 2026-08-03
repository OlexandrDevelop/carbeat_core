<?php

namespace App\Http\Services;

use App\Enums\AppBrand;
use App\Helpers\PhoneHelper;
use App\Models\Master;
use App\Models\User;
use Carbon\Carbon;
use Daaner\TurboSMS\Facades\TurboSMS;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

class ClaimService
{
    private const int|float REDIS_TTL_SECONDS = 5 * 60; // 5 minutes

    private const int CODE_LENGTH = 6;

    public function __construct(
        private readonly PhoneHelper $phoneHelper
    ) {}

    /**
     * Resolve a master by its (unclaimed) claim token.
     *
     * @throws ModelNotFoundException
     */
    public function findMasterByToken(string $token): Master
    {
        return Master::where('claim_token', $token)->with(['city', 'services'])->firstOrFail();
    }

    /**
     * Get public information about master by claim token.
     */
    public function getPublicInfo(string $token): array
    {
        $master = $this->findMasterByToken($token);

        return [
            'status' => 'ok',
            'master_id' => $master->id,
            'is_claimed' => (bool) $master->is_claimed,
            'master_phone' => $master->phone,
        ];
    }

    /**
     * Send SMS code for claim verification.
     *
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function sendSms(int $masterId, string $phone): array
    {
        $master = Master::findOrFail($masterId);

        if ($master->is_claimed) {
            throw new Exception('Master profile already claimed', 409);
        }

        // Generate claim token if not exists
        if (empty($master->claim_token)) {
            $master->claim_token = Str::random(40);
            $master->save();
        }

        $normalizedPhone = $this->phoneHelper->normalize($phone);
        $code = $this->generateCode();

        $this->storeCodeInRedis($master->id, $normalizedPhone, $code);
        $this->sendClaimMessage($normalizedPhone, $master, $code);

        return ['status' => 'sent'];
    }

    /**
     * Verify claim code and complete master claim process, linking the
     * master to a (found-or-created) User. Returns the linked User; callers
     * decide how to establish access from there (JWT for mobile, web
     * session for the browser claim flow).
     *
     * @throws Exception
     * @throws Throwable
     */
    public function verifyAndClaim(int $masterId, string $phone, string $code): User
    {
        $master = Master::findOrFail($masterId);
        $normalizedPhone = $this->phoneHelper->normalize($phone);

        $cachedData = $this->getCachedCode($master->id);

        if (! $cachedData) {
            throw new Exception('code_expired', 410);
        }

        $this->validateCode($cachedData, $code, $normalizedPhone);

        $user = $this->claimVerifiedMaster($master, $normalizedPhone);
        $this->clearCachedCode($master->id);

        return $user;
    }

    /**
     * Claim a master and link it to a User, given that the caller has
     * already verified the requester owns $normalizedPhone by whatever OTP
     * channel is appropriate for their flow (this method does no code
     * validation of its own). Used by verifyAndClaim() (token + Redis-code
     * claim flow) and by phone-first flows that verify via SmsService
     * instead (e.g. the guest-map onboarding flow).
     *
     * @throws Throwable
     */
    public function claimVerifiedMaster(Master $master, string $normalizedPhone): User
    {
        return DB::transaction(function () use ($master, $normalizedPhone) {
            $this->claimMaster($master, $normalizedPhone);

            return $this->ensureUserExists($master, $normalizedPhone);
        });
    }

    /**
     * Build deep link URL for claim process.
     */
    public function buildDeepLink(?int $masterId): string
    {
        $scheme = config('app.deep_links.scheme', AppBrand::CARBEAT);
        $host = config('app.deep_links.host', 'claim');
        $query = $masterId ? '?master_id='.$masterId : '';

        return "$scheme://$host/$query";
    }

    /**
     * Generate random 6-digit code.
     */
    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Store verification code in Redis.
     */
    private function storeCodeInRedis(int $masterId, string $phone, string $code): void
    {
        $payload = json_encode([
            'code' => $code,
            'phone' => $phone,
        ]);

        $redisKey = $this->buildRedisKey($masterId);
        Redis::setex($redisKey, self::REDIS_TTL_SECONDS, $payload);
    }

    /**
     * Get cached verification code from Redis.
     */
    private function getCachedCode(int $masterId): ?array
    {
        $redisKey = $this->buildRedisKey($masterId);
        $cached = Redis::get($redisKey);

        if (! $cached) {
            return null;
        }

        return json_decode($cached, true);
    }

    /**
     * Clear cached verification code from Redis.
     */
    private function clearCachedCode(int $masterId): void
    {
        $redisKey = $this->buildRedisKey($masterId);
        Redis::del($redisKey);
    }

    /**
     * Validate verification code and phone.
     *
     * @throws Exception
     */
    private function validateCode(?array $cachedData, string $code, string $phone): void
    {
        if (! $cachedData || ! isset($cachedData['code']) || $cachedData['code'] !== $code) {
            throw new Exception('invalid_code', 422);
        }

        if (! isset($cachedData['phone']) || $cachedData['phone'] !== $phone) {
            throw new Exception('phone_mismatch', 422);
        }
    }

    /**
     * Claim master profile.
     */
    private function claimMaster(Master $master, string $phone): void
    {
        $master->is_claimed = true;
        $master->phone_verified_at = Carbon::now();
        $master->claim_token = null;
        $master->contact_phone = $phone;
        $master->save();
    }

    /**
     * Ensure user exists and is linked to master.
     */
    private function ensureUserExists(Master $master, string $phone): User
    {
        $user = $master->user;

        if (! $user) {
            $user = User::firstOrCreate(
                ['phone' => $phone],
                ['name' => $master->name]
            );
            $master->user()->associate($user);
            $master->save();
        } else {
            if ($user->phone !== $phone) {
                $user->phone = $phone;
            }
            if (empty($user->name)) {
                $user->name = $master->name;
            }
        }

        if (is_null($user->phone_verified_at)) {
            $user->phone_verified_at = Carbon::now();
        }
        $user->save();

        return $user;
    }

    /**
     * Send claim SMS message.
     *
     * @throws Exception
     */
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
        } catch (Throwable $e) {
            Log::error('Failed to send claim SMS', [
                'master_id' => $master->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Build Redis key for claim verification code.
     */
    private function buildRedisKey(int $masterId): string
    {
        return "claim_sms:{$masterId}";
    }
}
