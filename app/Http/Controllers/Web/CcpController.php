<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\CcpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CcpController extends Controller
{
    public function info(Request $request): JsonResponse
    {
        $request->validate(['ccp' => 'required|string']);

        $service = new CcpService($request->ccp);

        return $this->successResponse([
            'key' => $service->getKey(),
            'rip' => $service->getRip(),
        ]);
    }
}
