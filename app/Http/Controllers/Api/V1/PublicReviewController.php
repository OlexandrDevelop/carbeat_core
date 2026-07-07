<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddGuestReviewRequest;
use App\Http\Requests\AddReviewReplyRequest;
use App\Http\Resources\Api\V1\GuestReviewResource;
use App\Http\Services\Master\MasterService;

class PublicReviewController extends Controller
{
    public function store(AddGuestReviewRequest $request, string $id, MasterService $masterService): GuestReviewResource
    {
        $review = $masterService->addGuestReview((int) $id, $request->validated());

        return new GuestReviewResource($review);
    }

    public function reply(AddReviewReplyRequest $request, string $id, string $reviewId, MasterService $masterService): GuestReviewResource
    {
        $review = $masterService->replyToReview((int) $id, (int) $reviewId, $request->validated());

        return new GuestReviewResource($review);
    }
}
