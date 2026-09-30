<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Stats;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(private readonly StatsService $statsService) {}

    /**
     * List persisted monthly stats, optionally filtered by month range and branch.
     */
    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_SALES->value)) {
            return $response;
        }

        $query = Stats::query()->with('branch');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        } else {
            $query->whereNull('branch_id');
        }

        if ($request->filled('from_month')) {
            $query->where('month', '>=', Carbon::parse($request->string('from_month')->toString())->startOfMonth());
        }

        if ($request->filled('to_month')) {
            $query->where('month', '<=', Carbon::parse($request->string('to_month')->toString())->startOfMonth());
        }

        return $this->successResponse(
            $query->orderByDesc('month')->orderBy('branch_id')->get()->map(
                fn (Stats $stat) => [
                    'month'     => Carbon::parse($stat->month)->format('Y-m'),
                    'branch_id' => $stat->branch_id,
                    'branch'    => $stat->branch?->name,
                    'data'      => $stat->data,
                ]
            )
        );
    }

    /**
     * Compute live stats for a custom date range (on demand).
     */
    public function store(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_SALES->value)) {
            return $response;
        }

        $stats = $this->statsService->stats(
            $request->filled('branch_id') ? $request->integer('branch_id') : null,
            $request->string('from_date')->toString() ?: null,
            $request->string('to_date')->toString() ?: null,
        );

        return $this->successResponse($stats);
    }
}
