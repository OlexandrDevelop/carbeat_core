<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminSeoContentUpdateRequest;
use App\Http\Services\Admin\SeoContentAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeoContentController extends Controller
{
    public function __construct(
        private readonly SeoContentAdminService $service,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/SeoContent/Index');
    }

    public function list(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->list($request->only(['type', 'search'])),
        );
    }

    public function update(AdminSeoContentUpdateRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->save($request->validated()),
        );
    }
}
