<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseMembershipRequest;
use App\Http\Resources\MembershipResource;
use App\Models\MembershipType;
use App\Services\MembershipService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MembershipController extends Controller
{
    public function __construct(
        private readonly MembershipService $memberships,
        private readonly WalletService $wallet,
    ) {
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

    /**
     * Покупка абонемента с баланса
     *
     * Стоимость списывается с баланса клиента. Если денег не хватает, абонемент не выдаётся.
     *
     * @group Абонементы клиента
     * @authenticated
     * @bodyParam membership_type_id integer required Id типа абонемента. Example: 2
     * @response 201 {"data": {"id": 5, "status": "active", "price_paid": "3500.00"}}
     * @response 422 {"message": "Недостаточно средств на балансе. Пополните баланс."}
     */
    public function store(PurchaseMembershipRequest $request): JsonResponse
    {
        $user = $request->user();
        $type = MembershipType::findOrFail($request->validated('membership_type_id'));

        $membership = DB::transaction(function () use ($user, $type) {
            $membership = $this->memberships->purchase($user, $type);
            $this->wallet->charge($user, (float) $type->price, "Покупка абонемента «{$type->name}»");

            return $membership;
        });

        return (new MembershipResource($membership))->response()->setStatusCode(201);
    }
}