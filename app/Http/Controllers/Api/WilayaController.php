<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\WilayaResource;
use App\Services\WilayaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayaController extends Controller
{
    public function __construct(private readonly WilayaService $wilayaService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_WILAYAS->value)) {
            return $response;
        }

        return $this->successResponse(WilayaResource::collection($this->wilayaService->list($request)));
    }
}
