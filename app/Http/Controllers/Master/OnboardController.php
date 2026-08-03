<?php

namespace App\Http\Controllers\Master;

use App\Helpers\PhoneHelper;
use App\Helpers\PhotoHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\OnboardVerifyRequest;
use App\Http\Requests\SendSmsCodeRequest;
use App\Http\Resources\Api\V1\MasterResource;
use App\Http\Services\Appointment\AppointmentRedisService;
use App\Http\Services\ClaimService;
use App\Http\Services\Master\MasterService;
use App\Http\Services\MasterCrmService;
use App\Http\Services\Realtime\RealtimePublisher;
use App\Http\Services\SmsService;
use App\Http\Services\UserService;
use App\Models\Master;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Single phone-first entry point for "become a master" from the public
 * guest map: the caller doesn't need to know in advance whether they're
 * registering a brand-new business or confirming an already-imported one —
 * the phone number alone determines the branch once the code is verified.
 */
class OnboardController extends Controller
{
    public function sendCode(SendSmsCodeRequest $request, SmsService $smsService, PhoneHelper $phoneHelper, PhotoHelper $photoHelper): JsonResponse
    {
        $phone = $phoneHelper->normalize($request->input('phone'));

        $smsService->generateAndSendCode($phone);

        $master = Master::with('city')->where('contact_phone', $phone)->first();

        if (! $master) {
            return response()->json(['status' => 'sent', 'mode' => 'register', 'preview' => null]);
        }

        return response()->json([
            'status' => 'sent',
            'mode' => $master->is_claimed ? 'login' : 'claim',
            'preview' => [
                'name' => $master->name,
                'photo' => $photoHelper->publicUrl($master->main_photo),
                'city' => $master->city?->name,
            ],
        ]);
    }

    public function verify(
        OnboardVerifyRequest $request,
        SmsService $smsService,
        PhoneHelper $phoneHelper,
        MasterService $masterService,
        MasterCrmService $crmService,
        UserService $userService,
        ClaimService $claimService,
        AppointmentRedisService $appointmentRedisService,
        RealtimePublisher $realtimePublisher
    ): JsonResponse {
        $phone = $phoneHelper->normalize($request->validated('phone'));

        if (! $smsService->verifyCode($phone, $request->validated('code'))) {
            return response()->json(['error' => 'invalid_code', 'message' => 'Невірний код.'], 400);
        }

        $master = Master::where('contact_phone', $phone)->first();

        if (! $master) {
            $missing = array_filter(
                ['name', 'service_id', 'latitude', 'longitude'],
                fn (string $field) => blank($request->validated($field))
            );

            if ($missing !== []) {
                return response()->json([
                    'error' => 'registration_incomplete',
                    'message' => 'Заповніть назву, категорію послуги та дозвольте визначення локації.',
                ], 422);
            }

            $user = $this->registerNewMaster($request, $phone, $masterService, $crmService, $userService, $appointmentRedisService, $realtimePublisher);
        } elseif (! $master->is_claimed) {
            $user = $claimService->claimVerifiedMaster($master, $phone);
            $crmService->ensureDefaultBay($master->fresh());
        } else {
            $user = $this->loginExistingMaster($master, $phone, $userService, $crmService);
        }

        Auth::guard('web')->login($user, true);

        return response()->json(['status' => 'ok']);
    }

    private function registerNewMaster(
        OnboardVerifyRequest $request,
        string $phone,
        MasterService $masterService,
        MasterCrmService $crmService,
        UserService $userService,
        AppointmentRedisService $appointmentRedisService,
        RealtimePublisher $realtimePublisher
    ): User {
        $master = $masterService->createOrUpdate([
            'phone' => $phone,
            'name' => (string) $request->validated('name'),
            'description' => '',
            'service_id' => (int) $request->validated('service_id'),
            'latitude' => (float) $request->validated('latitude'),
            'longitude' => (float) $request->validated('longitude'),
        ]);

        $crmService->ensureDefaultBay($master);

        $user = $userService->createOrUpdateFromMaster($master);

        $available = $appointmentRedisService->isAvailableFlag($master->id, $master->app);
        $payload = (new MasterResource($master, [$master->id => $available]))->toArray($request);
        $realtimePublisher->publishMasterCreated($master, $payload);

        return $user;
    }

    private function loginExistingMaster(Master $master, string $phone, UserService $userService, MasterCrmService $crmService): User
    {
        $user = $userService->findUserByPhone($phone);
        if (! $user) {
            $user = User::create(['phone' => $phone, 'name' => 'User '.substr($phone, -4)]);
        }
        $userService->attachUserToMasterByPhone($phone, $user);

        if (is_null($user->phone_verified_at)) {
            $user->phone_verified_at = now();
        }
        $user->last_login_at = now();
        $user->save();

        $crmService->ensureDefaultBay($master);

        return $user;
    }
}
