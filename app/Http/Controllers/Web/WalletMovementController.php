<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\WalletMovementResource;
use App\Services\WalletMovementService;
use Illuminate\Http\JsonResponse;

class WalletMovementController extends Controller
{
    public function __construct(private readonly WalletMovementService $walletMovementService) {}

    public function index(): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_WALLET->value)) {
            return $response;
        }

        try {
            $movements = $this->walletMovementService->list();

            return $this->successResponse(WalletMovementResource::collection($movements));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
