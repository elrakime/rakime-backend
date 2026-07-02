<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\CcpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CcpController extends Controller
{
    public function getKey(Request $request): JsonResponse
    {
        $request->validate(['ccp' => 'required|string']);

        $key = (new CcpService($request->ccp))->getKey();

        return $this->successResponse(['key' => $key]);
    }

    public function getRip(Request $request): JsonResponse
    {
        $request->validate(['ccp' => 'required|string']);

        $rip = (new CcpService($request->ccp))->getRip();

        return $this->successResponse(['rip' => $rip]);
    }
}
