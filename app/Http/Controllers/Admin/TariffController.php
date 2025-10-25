<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminTariffListRequest;
use App\Http\Requests\Admin\AdminTariffSaveRequest;
use App\Http\Resources\Admin\AdminTariffResource;
use App\Http\Services\Admin\TariffAdminService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class TariffController extends Controller
{
    public function __construct(private readonly TariffAdminService $service)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Tariffs/Index');
    }

    public function edit(int $id): Response
    {
        return Inertia::render('Admin/Tariffs/Edit', ['tariffId' => $id]);
    }

    public function list(AdminTariffListRequest $request): JsonResponse
    {
        $paginator = $this->service->list($request->validated());
        return response()->json([
            'data' => AdminTariffResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function get(int $id): JsonResponse
    {
        return response()->json(new AdminTariffResource($this->service->get($id)));
    }

    public function create(AdminTariffSaveRequest $request): JsonResponse
    {
        return response()->json(new AdminTariffResource($this->service->create($request->validated())));
    }

    public function update(int $id, AdminTariffSaveRequest $request): JsonResponse
    {
        return response()->json(new AdminTariffResource($this->service->update($id, $request->validated())));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->destroy($id);
        return response()->json(['status' => 'ok']);
    }
}
