<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Batch\StoreBatchRequest;
use App\Http\Requests\Web\Batch\UpdateBatchRequest;
use App\Http\Resources\Web\BatchResource;
use App\Models\Batch;
use App\Models\Stock;
use App\Services\BatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function __construct(private readonly BatchService $batchService) {}

    public function index(Request $request, Stock $stock): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_BATCHES->value)) {
            return $response;
        }

        $batches = $this->batchService->list($request, $stock);

        return $this->successResponse(BatchResource::collection($batches));
    }

    public function store(StoreBatchRequest $request, Stock $stock): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_BATCHES->value)) {
            return $response;
        }

        $validated = $this->validateRequest($request);

        $batch = $this->batchService->create($stock, $validated);

        return $this->successResponse(new BatchResource($batch), statusCode: 201);
    }

    public function show(Stock $stock, Batch $batch): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_BATCHES->value)) {
            return $response;
        }

        try {
            $batch = $this->batchService->show($batch);

            return $this->successResponse(new BatchResource($batch));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateBatchRequest $request, Stock $stock, Batch $batch): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_BATCHES->value)) {
            return $response;
        }

        try {
            $validated = $this->validateRequest($request);

            $batch = $this->batchService->update($batch, $validated);

            return $this->successResponse(new BatchResource($batch));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Stock $stock, Batch $batch): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_BATCHES->value)) {
            return $response;
        }

        try {
            $this->batchService->delete($batch);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
