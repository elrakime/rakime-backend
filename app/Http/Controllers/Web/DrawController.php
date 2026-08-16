<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\DrawResource;
use App\Models\Contract;
use App\Services\DrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrawController extends Controller
{
    public function __construct(private readonly DrawService $drawService) {}

    public function index(Request $request, Contract $contract): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($contract)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::VIEW_DRAWS->value)) {
            return $response;
        }

        return $this->successResponse(
            DrawResource::collection($this->drawService->list($request, $contract)),
        );
    }
}
