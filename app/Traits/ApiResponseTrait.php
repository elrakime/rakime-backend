<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

trait ApiResponseTrait
{
    protected function validateRequest(Request $request, array $rules = [])
    {
        $rules = empty($rules) ? $request->rules() : $rules;
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            abort(response()->json([
                'status'  => 0,
                'message' => trans('http-statuses.422'),
                'errors'  => $validator->errors(),
            ], 422));
        }

        return $validator->validated();
    }

    protected function successResponse($data = null, $message = null, $statusCode = 200): JsonResponse
    {
        $message = $message ?? trans("http-statuses.{$statusCode}");

        $response = [
            'status'  => 1,
            'message' => $message,
        ];

        if ($data instanceof ResourceCollection) {
            $paginator = $data->resource;

            if ($paginator instanceof LengthAwarePaginator) {
                $response['data'] = $data->collection->values();
                $response['meta'] = [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ];
                $response['links'] = [
                    'first' => $paginator->url(1),
                    'last'  => $paginator->url($paginator->lastPage()),
                    'prev'  => $paginator->previousPageUrl(),
                    'next'  => $paginator->nextPageUrl(),
                ];
            } else {
                $response['data'] = $data->resolve();
            }
        } elseif ($data instanceof JsonResource) {
            $response['data'] = $data->resolve();
        } elseif ($data instanceof LengthAwarePaginator) {
            $response['data'] = $data->items();
            $response['meta'] = [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage(),
            ];
            $response['links'] = [
                'first' => $data->url(1),
                'last'  => $data->url($data->lastPage()),
                'prev'  => $data->previousPageUrl(),
                'next'  => $data->nextPageUrl(),
            ];
        } elseif ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    protected function errorResponse($message = null, $statusCode = 400): JsonResponse
    {
        $statusCode = (! Lang::has("http-statuses.{$statusCode}")) ? 400 : $statusCode;
        $message = $message ?? trans("http-statuses.{$statusCode}");

        return response()->json([
            'status'  => 0,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Check if the authenticated user has a specific permission.
     *
     * @return JsonResponse|void
     */
    protected function authorizePermission(string $permission)
    {
        $user = auth()->user();

        if (! $user || ! $user->hasPermissionTo($permission)) {
            return $this->errorResponse(__('app.unauthorized'), 403);
        }
    }

    /**
     * Ensure the authenticated user has access to at least one of the given branches.
     * Admins bypass branch checks. Managers and Employees are limited to their assigned branches.
     *
     * @param  int|array|null  $allowed_branches  A branch ID or array of branch IDs the resource belongs to
     * @return JsonResponse|void
     */
    protected function authorizeBranchAccess($allowed_branches)
    {
        $user = auth()->user();

        if (! $user) {
            return $this->errorResponse(__('app.unauthorized'), 403);
        }

        // Admins have access to all branches
        if ($user->hasRole(\App\Enums\Role::ADMIN->value)) {
            return;
        }

        $allowedBranches = array_filter(array_map(
            fn ($id) => $id === null ? null : (int) $id,
            (array) $allowed_branches,
        ));

        if (empty($allowedBranches)) {
            return;
        }

        if (! $user->branches()->whereIn('branch_id', $allowedBranches)->exists()) {
            return $this->errorResponse(__('app.unauthorized'), 403);
        }
    }

    /**
     * Get the branch IDs the authenticated user is allowed to access.
     * Returns null for admins (meaning all branches).
     */
    protected function getUserBranchIds(): ?array
    {
        $user = auth()->user();

        if (! $user || $user->hasRole(\App\Enums\Role::ADMIN->value)) {
            return null;
        }

        return $user->branches()->pluck('branch_id')->toArray();
    }
}
