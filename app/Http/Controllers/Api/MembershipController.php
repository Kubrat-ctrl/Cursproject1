<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseMembershipRequest;
use App\Http\Resources\MembershipResource;
use App\Models\MembershipType;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MembershipController extends Controller
{
    public function __construct(private readonly MembershipService $memberships)
    {
    }

    /**
     * Абонементы текущего клиента с остатком посещений
     *
     * Доступ: клиент.
     *
     * @group Абонементы клиента
     * @authenticated
     * @response 200 {"data": [{"id": 4, "starts_on": "2026-11-01", "ends_on": "2026-12-01", "price_paid": "3500.00", "status": "active", "used_visits": 3, "visits_left": 9, "membership_type": {"id": 2, "name": "Месяц, 12 посещений"}}]}
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        return MembershipResource::collection($this->memberships->listFor($user));
    }

    public function store(PurchaseMembershipRequest $request): JsonResponse
    {
        $user = $request->user();
        $type = MembershipType::findOrFail($request->validated('membership_type_id'));

        $membership = $this->memberships->purchase($user, $type);

        return (new MembershipResource($membership))->response()->setStatusCode(201);
    }
}
