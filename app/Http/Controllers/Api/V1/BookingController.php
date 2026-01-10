<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Booking\CreateBookingRequest;
use App\Http\Requests\Api\V1\Booking\ListAvailableSlotsRequest;
use App\Http\Requests\Api\V1\Booking\ListBookingsRequest;
use App\Http\Requests\Api\V1\Booking\UpdateBookingStatusRequest;
use App\Http\Resources\Api\V1\BookingResource;
use App\Http\Resources\Api\V1\SlotResource;
use App\Http\Services\BookingService;
use App\Http\Services\SubscriptionService;
use App\Models\Booking;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService, private readonly SubscriptionService $subscriptionService)
    {
    }

    /**
     * For clients: get available slots for a master on a date.
     * Flutter should pass date (Y-m-d) and duration in minutes.
     */
    public function availableSlots(ListAvailableSlotsRequest $request, int $masterId): AnonymousResourceCollection
    {
        $data = $request->validated();
        $date = Carbon::parse($data['date']);
        $duration = (int) $data['duration_minutes'];

        $slots = $this->bookingService->getAvailableSlots($masterId, $date, $duration);

        return SlotResource::collection(collect($slots));
    }

    /**
     * Create a booking by an authenticated client.
     */
    public function create(CreateBookingRequest $request, int $masterId): BookingResource
    {
        $data = $request->validated();
        $user = JWTAuth::user();
        $clientId = $user?->client?->id;

        $start = Carbon::parse($data['start_time']);
        $end = Carbon::parse($data['end_time']);

        $booking = $this->bookingService->createBooking($masterId, $clientId, $start, $end, $data['note'] ?? null);

        return new BookingResource($booking);
    }

    /**
     * For masters: list bookings with filters. Requires active subscription.
     */
    public function masterBookings(ListBookingsRequest $request): AnonymousResourceCollection
    {
        $user = JWTAuth::user();
        $this->subscriptionService->assertUserHasActiveSubscription($user->id);

        $filters = $request->validated();
        if (empty($filters['master_id']) && $user->master) {
            $filters['master_id'] = $user->master->id;
        }

        $bookings = $this->bookingService->listBookings($filters);
        return BookingResource::collection($bookings);
    }

    /**
     * For masters: update booking status (confirm/cancel). Requires active subscription.
     */
    public function updateStatus(UpdateBookingStatusRequest $request, int $bookingId): BookingResource
    {
        $user = JWTAuth::user();
        $this->subscriptionService->assertUserHasActiveSubscription($user->id);

        $booking = Booking::with('master')->findOrFail($bookingId);

        if (empty($booking->master)) {
            abort(404, 'Booking not found');
        }

        // Optional: ensure the user owns this master
        if ($user->master && $user->master->id !== $booking->master_id) {
            abort(403, 'Forbidden');
        }

        $updated = $this->bookingService->updateStatus($booking, $request->validated()['status']);

        return new BookingResource($updated);
    }
}
