<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdminOrManager
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole([Role::ADMIN->value, Role::MANAGER->value])) {
            abort(response()->json([
                'status'  => 0,
                'message' => __('auth.forbidden_role'),
            ], 403));
        }

        return $next($request);
    }
}
