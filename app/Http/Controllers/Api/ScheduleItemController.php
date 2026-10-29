<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleItemIndexRequest;
use App\Http\Requests\StoreScheduleItemRequest;
use App\Http\Requests\UpdateScheduleItemRequest;
use App\Http\Resources\ScheduleItemResource;
use App\Models\ScheduleItem;
use App\Services\ScheduleItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ScheduleItemController extends Controller
{
    public function __construct(private readonly ScheduleItemService $items)
    {
    }

    public function index(ScheduleItemIndexRequest $request): AnonymousResourceCollection
    {
        return ScheduleItemResource::collection($this->items->paginate($request->validated()));
    }

    public function show(ScheduleItem $schedule_item): ScheduleItemResource
    {
        return new ScheduleItemResource($this->items->details($schedule_item));
    }

    public function store(StoreScheduleItemRequest $request): JsonResponse
    {
        $item = $this->items->create($request->validated());

        return (new ScheduleItemResource($item))->response()->setStatusCode(201);
    }

    public function update(UpdateScheduleItemRequest $request, ScheduleItem $schedule_item): ScheduleItemResource
    {
        return new ScheduleItemResource($this->items->update($schedule_item, $request->validated()));
    }

    public function cancel(ScheduleItem $schedule_item): ScheduleItemResource
    {
        return new ScheduleItemResource($this->items->cancel($schedule_item));
    }
}
