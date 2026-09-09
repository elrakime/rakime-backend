<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ImportFileRequest;
use App\Services\AccountImportService;
use Illuminate\View\View;
use RuntimeException;

class ImportController extends Controller
{
    public function __construct(
        private readonly AccountImportService $accountImportService,
    ) {}

    public function show(): View
    {
        return view('import');
    }

    public function store(ImportFileRequest $request): View
    {
        $drawDay = $request->integer('draw_day');

        try {
            $items = $this->accountImportService->import($request->file('file'), $drawDay);

            return view('import', [
                'items' => $items,
                'count' => count($items),
                'statusCounts' => $this->countByStatus($items),
            ]);
        } catch (RuntimeException $e) {
            return view('import', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Count items grouped by their normalized status.
     *
     * @param  array<int, array<string, string>>  $items
     * @return array<string, int>
     */
    private function countByStatus(array $items): array
    {
        $counts = array_fill_keys(
            ['paid_on_time', 'late_payment', 'postponed', 'failed'],
            0,
        );

        foreach ($items as $item) {
            $status = $item['status'] ?? null;

            if ($status !== null && isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return $counts;
    }
}
