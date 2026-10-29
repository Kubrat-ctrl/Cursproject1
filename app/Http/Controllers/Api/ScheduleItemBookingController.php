<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkoutBookingResource;
use App\Models\ScheduleItem;
use App\Services\BookingService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ScheduleItemBookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings)
    {
    }

    public function index(ScheduleItem $schedule_item): AnonymousResourceCollection
    {
        return WorkoutBookingResource::collection(
            $this->bookings->listForScheduleItem($schedule_item)
        );
    }
}
