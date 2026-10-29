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

    /** GET /api/v1/membership-types */
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

    /** PUT|PATCH /api/v1/membership-types/{membership_type}; сюда же относится снятие с продажи (is_active = false). */
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
