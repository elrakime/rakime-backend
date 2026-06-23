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
        if ($response = $this->authorizeBranchAccess($request->input('branch_id'))) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::MANAGE_TREASURY->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $treasury = $this->treasuryService->create($data);

            return $this->successResponse(new TreasuryResource($treasury), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Treasury $treasury): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($treasury)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::VIEW_TREASURY->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new TreasuryResource($this->treasuryService->show($treasury)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateTreasuryRequest $request, Treasury $treasury): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($treasury)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::MANAGE_TREASURY->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $treasury = $this->treasuryService->update($treasury, $data);

            return $this->successResponse(new TreasuryResource($treasury));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Treasury $treasury): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($treasury)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::MANAGE_TREASURY->value)) {
            return $response;
        }

        try {
            $this->treasuryService->delete($treasury);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
