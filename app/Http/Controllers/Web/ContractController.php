<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Contract\StoreContractRequest;
use App\Http\Resources\Web\ContractResource;
use App\Services\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(private readonly ContractService $contractService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_CONTRACTS->value)) {
            return $response;
        }

        return $this->successResponse(
            ContractResource::collection($this->contractService->list($request)),
        );
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($request->input('branch_id'))) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::CREATE_CONTRACTS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $contract = $this->contractService->create($data);

            return $this->successResponse(new ContractResource($contract), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
