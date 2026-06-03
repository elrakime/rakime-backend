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
}
