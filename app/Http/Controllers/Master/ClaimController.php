<?php

namespace App\Http\Controllers\Master;

use App\Helpers\PhoneHelper;
use App\Helpers\PhotoHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Claim\ClaimCodeRequest;
use App\Http\Services\ClaimService;
use App\Http\Services\MasterCrmService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser-based self-service claim flow: lets a master confirm ownership of
 * an imported/scraped profile (SMS OTP) without the mobile app installed,
 * then logs them straight into the web Master Portal. Reuses the same
 * ClaimService domain logic as the mobile JSON API
 * (App\Http\Controllers\Api\V1\ClaimController) — only the "how do we grant
 * access afterward" step differs (web session here vs JWT there).
 */
class ClaimController extends Controller
{
    public function show(string $token, ClaimService $claimService, PhotoHelper $photoHelper): Response
    {
        try {
            $master = $claimService->findMasterByToken($token);
        } catch (ModelNotFoundException) {
            return Inertia::render('Master/Auth/Claim', [
                'token' => $token,
                'notFound' => true,
                'master' => null,
                'deepLink' => '',
                'androidStoreUrl' => config('app.deep_links.android_store_url'),
                'iosStoreUrl' => config('app.deep_links.ios_store_url'),
            ])->toResponse(request())->setStatusCode(404);
        }

        return Inertia::render('Master/Auth/Claim', [
            'token' => $token,
            'notFound' => false,
            'master' => [
                'name' => $master->name,
                'photo' => $photoHelper->publicUrl($master->main_photo),
                'city' => $master->city?->name,
                'services' => $master->services->map(fn ($s) => $s->translate(app()->getLocale()))->values(),
                'maskedPhone' => $master->contact_phone ? PhoneHelper::mask($master->contact_phone) : null,
            ],
            'deepLink' => $claimService->buildDeepLink($master->id),
            'androidStoreUrl' => config('app.deep_links.android_store_url'),
            'iosStoreUrl' => config('app.deep_links.ios_store_url'),
        ])->toResponse(request());
    }

    public function sendCode(string $token, ClaimService $claimService): JsonResponse
    {
        try {
            $master = $claimService->findMasterByToken($token);
        } catch (ModelNotFoundException) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if (blank($master->contact_phone)) {
            return response()->json([
                'error' => 'no_phone',
                'message' => 'Не вдалося визначити номер телефону для цього профілю.',
            ], 422);
        }

        try {
            $result = $claimService->sendSms($master->id, $master->contact_phone);

            return response()->json($result);
        } catch (Exception $e) {
            if ($e->getCode() === 409) {
                return response()->json([
                    'error' => 'already_claimed',
                    'message' => 'Цей профіль вже підтверджено.',
                ], 409);
            }

            return response()->json([
                'error' => 'sms_failed',
                'message' => 'Не вдалося надіслати SMS. Спробуйте ще раз.',
            ], 400);
        }
    }

    public function verify(ClaimCodeRequest $request, string $token, ClaimService $claimService, MasterCrmService $crmService): JsonResponse
    {
        try {
            $master = $claimService->findMasterByToken($token);
        } catch (ModelNotFoundException) {
            return response()->json(['error' => 'not_found'], 404);
        }

        try {
            $user = $claimService->verifyAndClaim($master->id, $master->contact_phone, $request->validated('code'));
        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 422;

            return response()->json(['error' => $e->getMessage()], $statusCode);
        }

        $crmService->ensureDefaultBay($master->fresh());

        Auth::guard('web')->login($user, true);

        return response()->json(['status' => 'ok']);
    }
}
