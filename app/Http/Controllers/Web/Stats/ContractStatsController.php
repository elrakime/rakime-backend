<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Stats;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Stats\ContractStatsHistoryRequest;
use App\Http\Requests\Web\Stats\ContractStatsLiveRequest;
use App\Services\ContractStatsService;
use App\Services\StatsAggregator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ContractStatsController extends Controller
{
    public function __construct(
        private readonly ContractStatsService $statsService,
        private readonly StatsAggregator $aggregator,
    ) {}

    /**
     * List persisted monthly contract stats (total + per-month series).
     */
    public function index(ContractStatsHistoryRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_STATS->value)) {
            return $response;
        }

        $data = $this->aggregator->aggregateContracts(
            $request->filled('branch_id') ? $request->integer('branch_id') : null,
            $request->filled('account_id') ? $request->integer('account_id') : null,
            $request->filled('from_month') ? Carbon::parse($request->string('from_month')->toString()) : null,
            $request->filled('to_month') ? Carbon::parse($request->string('to_month')->toString()) : null,
        );

        return $this->successResponse($data);
    }

    /**
     * Compute live contract stats for a custom date range (on demand).
     */
    public function live(ContractStatsLiveRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_STATS->value)) {
            return $response;
        }

        $data = $this->statsService->stats(
            $request->filled('branch_id') ? $request->integer('branch_id') : null,
            $request->filled('account_id') ? $request->integer('account_id') : null,
            $request->filled('from_date') ? Carbon::parse($request->string('from_date')->toString())->startOfDay() : null,
            $request->filled('to_date') ? Carbon::parse($request->string('to_date')->toString())->endOfDay() : null,
        );

        return $this->successResponse($data);
    }
}
