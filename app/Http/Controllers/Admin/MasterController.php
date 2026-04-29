<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminMasterInviteRequest;
use App\Http\Requests\Admin\AdminMasterUpdateRequest;
use App\Http\Requests\Admin\AdminReviewStoreRequest;
use App\Http\Requests\Admin\AdminReviewUpdateRequest;
use App\Http\Requests\Admin\AdminMasterBulkDeleteRequest;
use App\Http\Resources\Admin\AdminMasterResource;
use App\Http\Resources\Admin\AdminMasterBulkDeleteResponse;
use App\Http\Services\Admin\MasterAdminService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterController extends Controller
{
    public function __construct(private readonly MasterAdminService $service)
    {
    }

    // Inertia pages
    public function index(): Response
    {
        return Inertia::render('Admin/Masters/Index', [
            'defaultInviteMessage' => config('app.master_invite_template'),
        ]);
    }

    public function edit(int $id): Response
    {
        return Inertia::render('Admin/Masters/Edit', [
            'masterId' => $id,
        ]);
    }

    // Admin API
    public function list(Request $request): JsonResponse
    {
        $masters = $this->service->listMasters($request->all());

        if ($masters instanceof LengthAwarePaginator) {
            return response()->json([
                'data' => AdminMasterResource::collection($masters->items()),
                'meta' => [
                    'current_page' => $masters->currentPage(),
                    'last_page' => $masters->lastPage(),
                    'per_page' => $masters->perPage(),
                    'total' => $masters->total(),
                ],
            ]);
        }

        return response()->json([
            'data' => AdminMasterResource::collection($masters),
        ]);
    }

    public function get(int $id): JsonResponse
    {
        $master = $this->service->getMaster($id);
        return response()->json(new AdminMasterResource($master));
    }

    public function update(AdminMasterUpdateRequest $request, int $id): JsonResponse
    {
        $master = $this->service->updateMaster($id, $request->validated());
        return response()->json(new AdminMasterResource($master));
    }

    public function services(): JsonResponse
    {
        return response()->json($this->service->listServices());
    }

    public function cities(Request $request): JsonResponse
    {
        $countryCode = $request->query('country_code') ?: null;
        return response()->json($this->service->listCities($countryCode));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteMaster($id);
        return response()->json(['status' => 'ok']);
    }

    public function destroyAll(AdminMasterBulkDeleteRequest $request): JsonResponse
    {
        $deleted = $this->service->deleteAllMasters();
        return response()->json(new AdminMasterBulkDeleteResponse(['deleted' => $deleted]));
    }

    public function invite(AdminMasterInviteRequest $request): JsonResponse
    {
        $result = $this->service->sendInvites(
            $request->input('master_ids', []),
            $request->input('message')
        );

        return response()->json($result);
    }

    // Reviews endpoints
    public function reviews(int $id): JsonResponse
    {
        return response()->json($this->service->getReviews($id));
    }

    public function storeReview(int $id, AdminReviewStoreRequest $request): JsonResponse
    {
        $review = $this->service->createReview($id, $request->validated());
        return response()->json($review);
    }

    public function updateReview(int $reviewId, AdminReviewUpdateRequest $request): JsonResponse
    {
        $review = $this->service->updateReview($reviewId, $request->validated());
        return response()->json($review);
    }

    public function deleteReview(int $reviewId): JsonResponse
    {
        $this->service->deleteReview($reviewId);
        return response()->json(['status' => 'ok']);
    }
}


