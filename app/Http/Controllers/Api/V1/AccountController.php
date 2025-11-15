<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeleteAccountRequest;
use App\Http\Resources\Api\V1\DeleteAccountResponse;
use App\Http\Services\SmsService;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function deleteByOtp(DeleteAccountRequest $request, SmsService $smsService): JsonResponse
    {
        $phone = (string) $request->input('phone');
        $code = (string) $request->input('sms_code');

        if (! $smsService->verifyCode($phone, $code)) {
            return response()->json(['error' => 'invalid_code'], 400);
        }

        /** @var User|null $user */
        $user = User::where('phone', $phone)->first();
        if (! $user) {
            return response()->json(['error' => 'user_not_found'], 404);
        }

        // Revoke all refresh tokens before deletion
        try {
            $user->refreshTokens()->update(['revoked' => true]);
        } catch (\Throwable $e) {
            // Ignore token revocation issues to proceed with deletion
        }

        // Delete master and its related data if present
        try {
            $master = $user->master;
            if ($master) {
                // Delete dependent relations to avoid foreign key constraints
                try { $master->gallery()->delete(); } catch (\Throwable $e) {}
                try { $master->reviews()->delete(); } catch (\Throwable $e) {}
                try { $master->bookings()->delete(); } catch (\Throwable $e) {}
                $master->delete();
            }
        } catch (\Throwable $e) {
            // Continue deletion even if some relations fail
        }

        // Finally delete user
        $user->delete();

        return response()->json(new DeleteAccountResponse(null));
    }
}



