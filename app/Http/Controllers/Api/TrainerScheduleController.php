<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleItemIndexRequest;
use App\Http\Resources\ScheduleItemResource;
use App\Services\ScheduleItemService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TrainerScheduleController extends Controller
{
    public function __construct(private readonly ScheduleItemService $items)
    {
    }

    public function index(ScheduleItemIndexRequest $request): AnonymousResourceCollection
    {
        $trainer = $this->actingUser($request);

        $filters = $request->validated();
        $filters['trainer_id'] = $trainer->id;

        return ScheduleItemResource::collection($this->items->paginate($filters));
    }
}
