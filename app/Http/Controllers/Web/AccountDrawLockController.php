<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\AccountDrawLock\StoreAccountDrawLockRequest;
use App\Http\Resources\Web\AccountDrawLockResource;
use App\Services\AccountDrawLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountDrawLockController extends Controller
{
    public function __construct(private readonly AccountDrawLockService $accountDrawLockService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_ACCOUNT_DRAW_LOCKS->value)) {
            return $response;
        }

        return $this->successResponse(
            AccountDrawLockResource::collection($this->accountDrawLockService->list($request)),
        );
    }

    public function store(StoreAccountDrawLockRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_ACCOUNT_DRAW_LOCKS->value)) {
            return $response;
        }

        try {
            $lock = $this->accountDrawLockService->create($this->validateRequest($request));

            return $this->successResponse(new AccountDrawLockResource($lock), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
