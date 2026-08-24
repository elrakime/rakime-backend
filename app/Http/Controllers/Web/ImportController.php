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
            ]);
        } catch (RuntimeException $e) {
            return view('import', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
