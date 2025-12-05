<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterClientRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Services\ClientService;
use App\Http\Services\UserService;
use App\Http\Services\TokenService;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    /**
     * Register a new client.
     *
     * @param  RegisterClientRequest  $request  The request object containing client registration data.
     * @param  UserService  $userService  The service handling user-related operations.
     * @param  ClientService  $clientService  The service handling client-related operations.
     * @return JsonResponse The response object containing the result of the registration process.
     */
    public function register(RegisterClientRequest $request, UserService $userService, ClientService $clientService, TokenService $tokenService): JsonResponse
    {
        $validatedData = $request->validated();
        $user = $userService->createOrUpdateForClient($validatedData);
        $validatedData['user_id'] = $user->id;
        $clientService->createOrUpdate($validatedData);

        $accessToken = $tokenService->createAccessToken($user);
        $refreshModel = $tokenService->createRefreshToken($user);
        $expiresIn = 60 * config('auth.access_token_ttl', 15);

        return response()->json([
            'user' => new UserResource($user),
            'access_token' => $accessToken,
            'refresh_token' => $refreshModel->plain_token,
            'expires_in' => $expiresIn,
            // Backward compatibility
            'token' => $accessToken,
        ]);
    }
}
