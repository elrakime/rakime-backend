<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Treasury\StoreTreasuryRequest;
use App\Http\Requests\Web\Treasury\UpdateTreasuryRequest;
use App\Http\Resources\Web\TreasuryResource;
use App\Models\Treasury;
use App\Services\TreasuryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TreasuryController extends Controller
{
    public function __construct(private readonly TreasuryService $treasuryService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_TREASURY->value)) {
            return $response;
        }

        return $this->successResponse(TreasuryResource::collection($this->treasuryService->list($request)));
    }

    public function store(StoreTreasuryRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_TREASURY->value)) {
            return $response;
        }

        $treasury = $this->treasuryService->create($this->validateRequest($request));

        return $this->successResponse(new TreasuryResource($treasury), statusCode: 201);
    }

    public function show(Treasury $treasury): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_TREASURY->value)) {
            return $response;
        }

        return $this->successResponse(new TreasuryResource($this->treasuryService->show($treasury)));
    }

    public function update(UpdateTreasuryRequest $request, Treasury $treasury): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_TREASURY->value)) {
            return $response;
        }

        $treasury = $this->treasuryService->update($treasury, $this->validateRequest($request));

        return $this->successResponse(new TreasuryResource($treasury));
    }

    public function destroy(Treasury $treasury): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_TREASURY->value)) {
            return $response;
        }

        $this->treasuryService->delete($treasury);

        return $this->successResponse(message: __('app.deleted'));
    }
}
