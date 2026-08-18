<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\InstallmentResource;
use App\Models\Contract;
use App\Services\InstallmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{
    public function __construct(private readonly InstallmentService $installmentService) {}

    public function index(Request $request, Contract $contract): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($contract->branch_id)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::VIEW_INSTALLMENTS->value)) {
            return $response;
        }

        return $this->successResponse(
            InstallmentResource::collection($this->installmentService->list($request, $contract)),
        );
    }
}
