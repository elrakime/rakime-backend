<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBatchRequest;
use App\Http\Requests\Api\UpdateBatchRequest;
use App\Http\Resources\Api\BatchResource;
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
        $batches = $this->batchService->list($request, $stock);

        return $this->successResponse(BatchResource::collection($batches));
    }

    public function store(StoreBatchRequest $request, Stock $stock): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $batch = $this->batchService->create($stock, $validated);

        return $this->successResponse(
            new BatchResource($batch),
            __('http-statuses.201'),
            201,
        );
    }

    public function show(Stock $stock, Batch $batch): JsonResponse
    {
        $batch = $this->batchService->show($batch);

        return $this->successResponse(new BatchResource($batch));
    }

    public function update(UpdateBatchRequest $request, Stock $stock, Batch $batch): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $batch = $this->batchService->update($batch, $validated);

        return $this->successResponse(new BatchResource($batch));
    }

    public function destroy(Stock $stock, Batch $batch): JsonResponse
    {
        $this->batchService->delete($batch);

        return $this->successResponse(null, __('http-statuses.200'));
    }
}
