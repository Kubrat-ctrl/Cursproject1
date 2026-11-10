<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/** Список занятий: data, links и meta формирует пагинатор. */
class ScheduleItemCollection extends ResourceCollection
{
    public $collects = ScheduleItemResource::class;
}
