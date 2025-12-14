<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddMasterGalleryPhotosRequest;
use App\Http\Requests\AddMasterRequest;
use App\Http\Requests\AddReviewRequest;
use App\Http\Requests\Availability\SetAvailableMasterRequest;
use App\Http\Requests\Availability\SetUnavailableMasterRequest;
use App\Http\Requests\DeleteMasterGalleryPhotoRequest;
use App\Http\Requests\GetMasterRequest;
use App\Http\Requests\ImportExternalMasterRequest;
use App\Http\Requests\UpdateMasterRequest;
use App\Http\Requests\UpdateMasterServicesRequest;
use App\Http\Resources\Api\V1\AvailabilityResponse;
use App\Http\Resources\Api\V1\MasterResource;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Services\Appointment\AppointmentRedisService;
use App\Http\Services\ClientService;
use App\Http\Services\Master\MasterAvailabilityService;
use App\Http\Services\Master\MasterFetcher;
use App\Http\Services\Master\MasterGalleryService;
use App\Http\Services\Master\MasterService;
use App\Http\Services\Realtime\RealtimePublisher;
use App\Http\Services\SmsService;
use App\Http\Services\TokenService;
use App\Http\Services\UserService;
use App\Models\Master;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class MasterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  GetMasterRequest  $request  The request instance containing validation and authorization logic.
     */
    public function index(GetMasterRequest $request, MasterFetcher $masterFetcher): JsonResponse
    {
        return $masterFetcher->fetch($request->validated());
    }

    /**
     * Retrieve the master resource by its ID.
     *
     * @param  int  $id  The ID of the master resource to retrieve.
     * @return MasterResource The master resource corresponding to the given ID.
     */
    public function getMaster(int $id, MasterService $masterService): MasterResource
    {
        $master = $masterService->getMasterById($id);

        return new MasterResource($master);
    }

    /**
     * @throws Exception
     */
    // Inject RealtimePublisher for publishing events
    public function verifyAndRegister(AddMasterRequest $request, MasterService $masterService, SmsService $smsService, UserService $userService, AppointmentRedisService $appointmentRedisService, TokenService $tokenService, RealtimePublisher $realtimePublisher): JsonResponse
    {
        $data = $request->validated();

        if (! $smsService->verifyCode($data['phone'], $data['sms_code'])) {
            return response()->json(['error' => 'Wrong code'], 400);
        }

        $master = $masterService->createOrUpdate($data);

        $user = $userService->createOrUpdateFromMaster($master);

        $accessToken = $tokenService->createAccessToken($user);
        $refreshModel = $tokenService->createRefreshToken($user);
        $expiresIn = 60 * config('auth.access_token_ttl', 15);

        // Publish "master created" event to Redis for realtime map updates (delegated to RealtimePublisher)
        $available = $appointmentRedisService->isAvailableFlag($master->id);
        $payload = new MasterResource($master, [$master->id => $available])->toArray($request);
        $realtimePublisher->publishMasterCreated($master, $payload);

        return response()->json([
            'master' => new MasterResource($master),
            'user' => new UserResource($user),
            'access_token' => $accessToken,
            'refresh_token' => $refreshModel->plain_token,
            'expires_in' => $expiresIn,
            // Backward compatibility for legacy apps expecting "token"
            'token' => $accessToken,
        ]);
    }

    public function addReview(AddReviewRequest $request, MasterService $masterService): ReviewResource
    {
        $data = $request->validated();
        /** @var User $user */
        $user = JWTAuth::user();
        $data['user_id'] = $user->id;
        $review = $masterService->addReview($data);

        return new ReviewResource($review);
    }

    public function setUnavailable(
        SetUnavailableMasterRequest $request,
        string $id,
        MasterAvailabilityService $availabilityService
    ): JsonResponse {
        $id = (int) $id;

        $availabilityService->setUnavailable($id);

        return new AvailabilityResponse([
            'message' => 'Master is unavailable',
            'master_id' => $id,
            'available' => false,
        ])->response();
    }

    public function getAvailability(
        string $id,
        MasterAvailabilityService $availabilityService
    ): JsonResponse {
        $id = (int) $id;

        $availability = $availabilityService->getAvailability($id);

        return response()->json(['availability' => $availability]);
    }

    /**
     * Set the master as available (flag only, for map/list visibility).
     */
    public function setAvailable(
        SetAvailableMasterRequest $request,
        string $id,
        MasterAvailabilityService $availabilityService
    ): JsonResponse {
        $id = (int) $id;
        $data = $request->validated();

        $availabilityService->setAvailable(
            $id,
            isset($data['duration']) ? (int) $data['duration'] : null,
            $data['start_time'] ?? null
        );

        return new AvailabilityResponse([
            'message' => 'Master is available',
            'master_id' => $id,
            'available' => true,
        ])->response();
    }

    public function storeFromExternal(int $serviceId, ImportExternalMasterRequest $request, MasterService $masterService, ClientService $clientService)
    {
        $master = $masterService->importFromExternal($serviceId, $request->validated(), $clientService);

        return new MasterResource($master);
    }

    public function updateProfile(UpdateMasterRequest $request, int $id, MasterService $masterService): JsonResponse
    {
        $master = Master::findOrFail($id);
        $this->authorize('update', $master); // ensure policy exists or skip

        $data = $request->validated();
        $masterService->updateDetails($master, $data);

        return response()->json(['master' => new MasterResource($master->refresh())]);
    }

    public function updateOwnProfile(UpdateMasterRequest $request, MasterService $masterService): JsonResponse
    {
        /** @var User|null $user */
        $user = JWTAuth::user();

        if (! $user || ! $user->master) {
            return response()->json(['error' => 'master_not_found'], 404);
        }

        $master = $user->master;
        $this->authorize('update', $master);

        $masterService->updateDetails($master, $request->validated());

        return response()->json(['master' => new MasterResource($master->refresh())]);
    }

    /**
     * Update master's additional services (pivot) without touching main service_id.
     */
    public function updateServices(
        UpdateMasterServicesRequest $request,
        int $id,
        MasterService $masterService
    ): JsonResponse {
        $master = Master::findOrFail($id);
        $this->authorize('update', $master);

        $serviceIds = $request->validated()['service_ids'] ?? [];
        $masterService->updateServices($master, $serviceIds);

        return response()->json(['status' => 'ok']);
    }

    public function addGalleryPhotos(
        AddMasterGalleryPhotosRequest $request,
        int $id,
        MasterGalleryService $galleryService
    ): JsonResponse {
        $master = Master::findOrFail($id);
        $this->authorize('update', $master);

        $galleryService->addPhotos($master, $request->photos);

        return response()->json(['message' => 'uploaded']);
    }

    public function deleteGalleryPhoto(
        DeleteMasterGalleryPhotoRequest $request,
        int $id,
        int $photoId,
        MasterGalleryService $galleryService
    ): JsonResponse {
        $master = Master::findOrFail($id);
        $this->authorize('update', $master);

        $galleryService->deletePhoto($master, $photoId);

        return response()->json(['message' => 'deleted']);
    }
}
