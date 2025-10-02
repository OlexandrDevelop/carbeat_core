<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\AvailabilityInterval;
use App\Helpers\AddressHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddMasterRequest;
use App\Http\Requests\AddReviewRequest;
use App\Http\Requests\Availability\SetAvailableMasterRequest;
use App\Http\Requests\Availability\SetUnavailableMasterRequest;
use App\Http\Requests\DeleteMasterGalleryPhotoRequest;
use App\Http\Requests\GetMasterRequest;
use App\Http\Requests\ImportExternalMasterRequest;
use App\Http\Requests\UpdateMasterRequest;
use App\Http\Requests\UpdateMasterServicesRequest;
use App\Http\Resources\Api\V1\MasterResource;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Services\Appointment\AppointmentRedisService;
use App\Http\Services\ClientService;
use App\Http\Services\Master\MasterFetcher;
use App\Http\Services\Master\MasterService;
use App\Http\Services\SmsService;
use App\Http\Services\UserService;
use App\Models\Master;
use App\Models\MasterGallery;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
    public function getMaster(int $id): MasterResource
    {
        $master = Master::with(['services', 'gallery', 'reviews.user'])->findOrFail($id);

        return new MasterResource($master);
    }

    /**
     * @throws Exception
     */
    public function verifyAndRegister(AddMasterRequest $request, MasterService $masterService, SmsService $smsService, UserService $userService): JsonResponse
    {
        $data = $request->validated();

        if (! $smsService->verifyCode($data['phone'], $data['sms_code'])) {
            return response()->json(['error' => 'Wrong code'], 400);
        }

        $master = $masterService->createOrUpdate($data);

        $user = $userService->createOrUpdateFromMaster($master);

        $token = JWTAuth::claims(['phone' => $user->phone])->fromUser($user);

        return response()->json([
            'master' => new MasterResource($master),
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function addReview(AddReviewRequest $request, MasterService $masterService): ReviewResource
    {
        $data = $request->validated();
        /** @var \App\Models\User $user */
        $user = JWTAuth::user();
        $data['user_id'] = $user->id;
        $review = $masterService->addReview($data);

        return new ReviewResource($review);
    }

    public function fillPlaceId(): JsonResponse
    {
        $masters = Master::all();
        foreach ($masters as $master) {
            $master->address = AddressHelper::getPlaceId($master->latitude, $master->longitude);
            $master->save();
        }

        return response()->json(['message' => 'Place id filled',
            'masters' => [
                'count' => $masters->count(),
                'first' => $masters->first()->place_id,
                'last' => $masters->last()->place_id,
            ]]);
    }

    public function setUnavailable(SetUnavailableMasterRequest $request, string $id, AppointmentRedisService $appointmentRedisService): JsonResponse
    {
        $id = (int) $id;

        // Availability: set flag to unavailable
        $appointmentRedisService->setUnavailableFlag($id);

        return response()->json(['message' => 'Master is unavailable']);
    }

    public function getAvailability(string $id, AppointmentRedisService $appointmentRedisService): JsonResponse
    {
        $id = (int) $id;

        $availability = $appointmentRedisService->isAvailableFlag($id);

        return response()->json(['availability' => $availability]);
    }

    /**
     * Set the master as available (flag only, for map/list visibility).
     */
    public function setAvailable(SetAvailableMasterRequest $request, string $id, AppointmentRedisService $appointmentRedisService): JsonResponse
    {
        $id = (int) $id;

        // Availability: set flag to available
        $appointmentRedisService->setAvailableFlag($id);

        return response()->json(['message' => 'Master is available']);
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

    /**
     * Update master's additional services (pivot) without touching main service_id.
     */
    public function updateServices(UpdateMasterServicesRequest $request, int $id): JsonResponse
    {
        $master = Master::findOrFail($id);
        $this->authorize('update', $master);

        $serviceIds = collect($request->validated()['service_ids'] ?? [])
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        // Ensure main service remains represented in pivot for consistency
        if ($master->service_id && ! $serviceIds->contains((int) $master->service_id)) {
            $serviceIds->push((int) $master->service_id);
        }

        $master->services()->sync($serviceIds->all());

        return response()->json(['status' => 'ok']);
    }

    public function addGalleryPhotos(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'photos' => ['required', 'array', 'max:10'],
            'photos.*' => ['required', new \App\Rules\Base64Image],
        ]);
        $master = Master::findOrFail($id);
        $this->authorize('update', $master);

        foreach ($request->photos as $img) {
            $path = app(\App\Helpers\PhotoHelper::class)->saveBase64($img);
            MasterGallery::create(['master_id' => $master->id, 'photo' => $path]);
        }

        return response()->json(['message' => 'uploaded']);
    }

    public function deleteGalleryPhoto(DeleteMasterGalleryPhotoRequest $request, int $id, int $photoId): JsonResponse
    {
        $master = Master::findOrFail($id);
        $this->authorize('update', $master);

        $photo = MasterGallery::where('master_id', $master->id)->where('id', $photoId)->firstOrFail();
        Storage::disk('public')->delete($photo->photo);
        $photo->delete();

        return response()->json(['message' => 'deleted']);
    }
}
