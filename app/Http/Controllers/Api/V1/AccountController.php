<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeleteAccountRequest;
use App\Http\Resources\Api\V1\DeleteAccountResponse;
use App\Http\Services\AccountService;
use App\Http\Services\SmsService;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly SmsService $smsService
    ) {}

    /**
     * @throws Exception
     */
    public function deleteByOtp(DeleteAccountRequest $request): JsonResponse
    {
        $phone = (string) $request->input('phone');
        $code = (string) $request->input('sms_code');

        if (! $this->smsService->verifyCode($phone, $code)) {
            return response()->json(['error' => 'invalid_code'], 400);
        }

        /** @var User|null $user */
        $user = User::where('phone', $phone)->first();
        if (! $user) {
            return response()->json(['error' => 'user_not_found'], 404);
        }

        $this->accountService->deleteAccount($user);

        return response()->json(new DeleteAccountResponse(null));
    }
}





