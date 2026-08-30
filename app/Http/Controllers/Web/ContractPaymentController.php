<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Contract\StorePaymentRequest;
use App\Http\Resources\Web\ContractPaymentResource;
use App\Models\Contract;
use App\Services\ContractPaymentService;
use Illuminate\Http\JsonResponse;

class ContractPaymentController extends Controller
{
    public function __construct(private readonly ContractPaymentService $contractPaymentService) {}

    public function store(StorePaymentRequest $request, Contract $contract): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($contract->branch_id)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::CREATE_PAYMENTS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $payment = $this->contractPaymentService->create(
                $contract,
                (float) $data['amount'],
                $data['note'] ?? null,
                isset($data['wallet_id']) ? (int) $data['wallet_id'] : null,
            );

            return $this->successResponse(new ContractPaymentResource($payment), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
