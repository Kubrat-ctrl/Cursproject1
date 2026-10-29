<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingIndexRequest;
use App\Http\Resources\WorkoutBookingResource;
use App\Models\ScheduleItem;
use App\Models\WorkoutBooking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings)
    {
    }

    public function index(BookingIndexRequest $request): AnonymousResourceCollection
    {
        $user = $this->actingUser($request);

        return WorkoutBookingResource::collection(
            $this->bookings->paginateFor($user, $request->validated())
        );
    }

    public function store(Request $request, ScheduleItem $schedule_item): JsonResponse
    {
        $user = $this->actingUser($request);

        $booking = $this->bookings->book($user, $schedule_item);

        return (new WorkoutBookingResource($booking))->response()->setStatusCode(201);
    }

    public function destroy(WorkoutBooking $booking): Response
    {
        $this->bookings->cancel($booking);

        return response()->noContent();
    }
}
