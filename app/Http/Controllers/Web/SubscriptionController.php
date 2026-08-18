<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\SubscriptionResource;
use App\Models\Contract;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptionService) {}

    public function index(Request $request, Contract $contract): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($contract->branch_id)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::VIEW_SUBSCRIPTIONS->value)) {
            return $response;
        }

        return $this->successResponse(
            SubscriptionResource::collection($this->subscriptionService->list($request, $contract)),
        );
    }
}
