<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\FinancialRecord\StoreFinancialRecordRequest;
use App\Http\Requests\Web\FinancialRecord\UpdateFinancialRecordRequest;
use App\Http\Resources\Web\FinancialRecordResource;
use App\Models\Client;
use App\Models\Contract;
use App\Models\FinancialRecord;
use App\Services\FinancialRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialRecordController extends Controller
{
    public function __construct(private readonly FinancialRecordService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_FINANCIAL_RECORDS->value)) {
            return $response;
        }

        $records = $this->service->list($request);

        return $this->successResponse(FinancialRecordResource::collection($records));
    }

    public function store(StoreFinancialRecordRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_FINANCIAL_RECORDS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        $client = Client::findOrFail($data['client_id']);

        if ($response = $this->authorizeBranchAccess($client->branch_id)) {
            return $response;
        }

        if (isset($data['contract_id'])) {
            $contract = Contract::findOrFail($data['contract_id']);

            if ($contract->client_id !== $client->id) {
                return $this->errorResponse(message: __('financial_records.contract_client_mismatch'), statusCode: 422);
            }
        }

        try {
            $record = $this->service->create($data);

            return $this->successResponse(
                new FinancialRecordResource($record),
                statusCode: 201,
            );
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function update(UpdateFinancialRecordRequest $request, FinancialRecord $financialRecord): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_FINANCIAL_RECORDS->value)) {
            return $response;
        }

        if ($response = $this->authorizeBranchAccess($financialRecord->client->branch_id)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $record = $this->service->update($financialRecord, $data);

            return $this->successResponse(
                new FinancialRecordResource($record),
            );
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function destroy(FinancialRecord $financialRecord): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_FINANCIAL_RECORDS->value)) {
            return $response;
        }

        if ($response = $this->authorizeBranchAccess($financialRecord->client->branch_id)) {
            return $response;
        }

        try {
            $this->service->delete($financialRecord);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
