<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Services\ZakatService;
use Illuminate\Http\JsonResponse;

class ZakatController extends Controller
{
    public function __construct(private readonly ZakatService $service)
    {
    }

    public function index(): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_ZAKAT->value)) {
            return $response;
        }

        return $this->successResponse($this->service->compute());
    }
}
