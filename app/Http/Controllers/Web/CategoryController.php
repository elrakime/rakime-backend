<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Category\StoreCategoryRequest;
use App\Http\Requests\Web\Category\UpdateCategoryRequest;
use App\Http\Resources\Web\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_CATEGORIES->value)) {
            return $response;
        }

        return $this->successResponse(CategoryResource::collection($this->categoryService->list($request)));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_CATEGORIES->value)) {
            return $response;
        }

        try {
            $category = $this->categoryService->create($this->validateRequest($request), $request);

            return $this->successResponse(new CategoryResource($category), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Category $category): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_CATEGORIES->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new CategoryResource($this->categoryService->show($category)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_CATEGORIES->value)) {
            return $response;
        }

        try {
            $category = $this->categoryService->update($category, $this->validateRequest($request), $request);

            return $this->successResponse(new CategoryResource($category));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_CATEGORIES->value)) {
            return $response;
        }

        try {
            $this->categoryService->delete($category);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
