<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\ContractStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Contract\ApproveContractRequest;
use App\Http\Requests\Web\Contract\ConfigureContractRequest;
use App\Http\Requests\Web\Contract\RejectContractRequest;
use App\Http\Requests\Web\Contract\StoreContractRequest;
use App\Http\Requests\Web\Contract\UpdateContractRequest;
use App\Http\Resources\Web\ContractResource;
use App\Models\Contract;
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

    public function show(Contract $contract): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($contract->branch_id)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::VIEW_CONTRACTS->value)) {
            return $response;
        }

        return $this->successResponse(
            new ContractResource($this->contractService->show($contract)),
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

    public function update(UpdateContractRequest $request, Contract $contract): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($contract->branch_id)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::UPDATE_CONTRACTS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $contract = $this->contractService->update($contract, $data);

            return $this->successResponse(new ContractResource($contract));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function approve(ApproveContractRequest $request, Contract $contract): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($contract->branch_id)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::APPROVE_CONTRACTS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $contract->assertCanTransitionTo(ContractStatus::APPROVED->value);

            $contract = $this->contractService->approve($contract, isset($data['max_amount']) ? (float) $data['max_amount'] : null);

            return $this->successResponse(new ContractResource($contract));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function reject(RejectContractRequest $request, Contract $contract): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($contract->branch_id)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::REJECT_CONTRACTS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $contract->assertCanTransitionTo(ContractStatus::REJECTED->value);

            $contract = $this->contractService->reject($contract, (bool) ($data['ban_client'] ?? false));

            return $this->successResponse(new ContractResource($contract));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function configure(ConfigureContractRequest $request, Contract $contract): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($contract->branch_id)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::CONFIGURE_CONTRACTS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $contract->assertCanTransitionTo(ContractStatus::CONFIGURED->value);

            $contract = $this->contractService->configure($contract, (int) $data['subscription_count'], $data['draw_date']);

            return $this->successResponse(new ContractResource($contract));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
