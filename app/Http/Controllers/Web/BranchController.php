<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Branch\StoreBranchRequest;
use App\Http\Requests\Web\Branch\UpdateBranchRequest;
use App\Http\Resources\Web\BranchResource;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(private readonly BranchService $branchService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_BRANCHES->value)) {
            return $response;
        }

        return $this->successResponse(BranchResource::collection($this->branchService->list($request)));
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_BRANCHES->value)) {
            return $response;
        }

        $branch = $this->branchService->create($this->validateRequest($request));

        return $this->successResponse(new BranchResource($branch), statusCode: 201);
    }

    public function show(Branch $branch): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_BRANCHES->value)) {
            return $response;
        }

        return $this->successResponse(new BranchResource($this->branchService->show($branch)));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_BRANCHES->value)) {
            return $response;
        }

        $branch = $this->branchService->update($branch, $this->validateRequest($request));

        return $this->successResponse(new BranchResource($branch));
    }

    public function destroy(Branch $branch): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_BRANCHES->value)) {
            return $response;
        }

        $this->branchService->delete($branch);

        return $this->successResponse(message: __('app.deleted'));
    }
}
