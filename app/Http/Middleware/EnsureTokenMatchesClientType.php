<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenMatchesClientType
{
    public function handle(Request $request, Closure $next, string $expectedClientType): Response
    {
        $clientType = $request->header('X-Client-Type');

        if ($clientType !== $expectedClientType) {
            abort(response()->json([
                'status'  => 0,
                'message' => __('auth.mismatch'),
            ], 401));
        }

        $token = $request->user()?->currentAccessToken();

        if (! $token || $token->name !== $expectedClientType) {
            abort(response()->json([
                'status'  => 0,
                'message' => __('auth.mismatch'),
            ], 401));
        }

        return $next($request);
    }
}
