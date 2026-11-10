<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMembershipTypeRequest;
use App\Http\Requests\UpdateMembershipTypeRequest;
use App\Http\Resources\MembershipTypeResource;
use App\Models\MembershipType;
use App\Services\MembershipTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MembershipTypeController extends Controller
{
    public function __construct(private readonly MembershipTypeService $types)
    {
    }

    /**
     * Список типов абонементов
     *
     * Доступ: любой, в том числе гость.
     *
     * @group Типы абонементов
     * @queryParam is_active boolean Только действующие тарифы. Example: true
     * @response 200 {"data": [{"id": 2, "name": "Месяц, 12 посещений", "price": "3500.00", "duration_days": 30, "visits_limit": 12, "is_active": true}], "links": {"first": "...", "last": "...", "prev": null, "next": "..."}, "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 1}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return MembershipTypeResource::collection(
            $this->types->paginate($request->only(['is_active']))
        );
    }

    /** GET /api/v1/membership-types/{membership_type} */
    public function show(MembershipType $membershipType): MembershipTypeResource
    {
        return new MembershipTypeResource($membershipType);
    }

    /** POST /api/v1/membership-types */
    public function store(StoreMembershipTypeRequest $request): JsonResponse
    {
        $type = $this->types->create($request->validated());

        return (new MembershipTypeResource($type))->response()->setStatusCode(201);
    }

    /**
     * Изменение типа абонемента
     *
     * Доступ: администратор.
     *
     * @group Типы абонементов
     * @authenticated
     * @urlParam membership_type integer required Идентификатор. Example: 2
     * @response 200 {"data": {"id": 2, "name": "Месяц, 12 посещений", "price": "3500.00", "duration_days": 30, "visits_limit": 12, "is_active": true}}
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     * @response 404 {"message": "Тип абонемента не найден"}
     * @response 422 {"message": "Ошибка валидации", "errors": {"field": ["Сообщение об ошибке"]}}
     */
    public function update(UpdateMembershipTypeRequest $request, MembershipType $membershipType): MembershipTypeResource
    {
        return new MembershipTypeResource(
            $this->types->update($membershipType, $request->validated())
        );
    }

    /** DELETE /api/v1/membership-types/{membership_type} */
    public function destroy(MembershipType $membershipType): Response
    {
        $this->types->delete($membershipType);

        return response()->noContent();
    }
}
