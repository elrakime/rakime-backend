<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Stats;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Stats\StatsRequest;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function __construct(private readonly StatsService $snapshotService) {}

    /**
     * Live-only snapshot stats (inventories, wallets, clients).
     */
    public function index(StatsRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_STATS->value)) {
            return $response;
        }

        $data = $this->snapshotService->snapshot(
            $request->filled('branch_id') ? $request->integer('branch_id') : null,
        );

        return $this->successResponse($data);
    }
}
