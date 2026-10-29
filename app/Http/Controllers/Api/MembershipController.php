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

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->actingUser($request);

        return MembershipResource::collection($this->memberships->listFor($user));
    }

    public function store(PurchaseMembershipRequest $request): JsonResponse
    {
        $user = $this->actingUser($request);
        $type = MembershipType::findOrFail($request->validated('membership_type_id'));

        $membership = $this->memberships->purchase($user, $type);

        return (new MembershipResource($membership))->response()->setStatusCode(201);
    }
}
