<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisitResource;
use App\Models\WorkoutBooking;
use App\Services\VisitService;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    public function __construct(private readonly VisitService $visits)
    {
    }

    public function store(WorkoutBooking $booking): JsonResponse
    {
        $visit = $this->visits->mark($booking);

        return (new VisitResource($visit))->response()->setStatusCode(201);
    }
}
