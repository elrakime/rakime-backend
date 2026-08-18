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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_FINANCIAL_RECORDS->value)) {
            return $response;
        }

        $query = FinancialRecord::query()->with(['client', 'contract']);

        if ($clientId = $request->integer('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($contractId = $request->integer('contract_id')) {
            $query->where('contract_id', $contractId);
        }

        $records = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());

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

        $revenues = $data['revenues'] ?? [];
        $expenses = $data['expenses'] ?? [];

        $income = array_sum($revenues) - array_sum($expenses);

        try {
            $record = FinancialRecord::create([
                'client_id'   => $client->id,
                'contract_id' => $data['contract_id'] ?? null,
                'revenues'    => $revenues,
                'expenses'    => $expenses,
                'income'      => $income,
                'note'        => $data['note'] ?? null,
            ]);

            return $this->successResponse(
                new FinancialRecordResource($record->load(['client', 'contract'])),
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

        $revenues = $data['revenues'] ?? $financialRecord->revenues ?? [];
        $expenses = $data['expenses'] ?? $financialRecord->expenses ?? [];

        $income = array_sum($revenues) - array_sum($expenses);

        try {
            $financialRecord->update([
                'revenues' => $revenues,
                'expenses' => $expenses,
                'income'   => $income,
                'note'     => $data['note'] ?? $financialRecord->note,
            ]);

            return $this->successResponse(
                new FinancialRecordResource($financialRecord->load(['client', 'contract'])),
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
            $financialRecord->delete();

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
