<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Models\Account;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountExportService
{
    /**
     * Export all configured contracts' subscriptions for the given account
     * as an Excel (.xls) spreadsheet.
     *
     * @param string|null $date Optional target month in "Y-m" format (e.g.
     *                          "2026-10"). When omitted, the next draw date
     *                          after the account's last lock is used.
     * @param array|null $branchIds Optional branch IDs to filter contracts by.
     */
    public function exportRegistrations(Account $account, ?string $date = null, ?array $branchIds = null): StreamedResponse
    {
        $contracts = $account->installmentContracts()
            ->where('status', ContractStatus::CONFIGURED)
            ->when(! empty($branchIds), fn ($query) => $query->whereIn('branch_id', $branchIds))
            ->whereHas('installments', function ($query) use ($account, $date) {
                $this->applyPeriodFilter($query, 'due_date', $account, $date);
            })
            ->with(['client', 'subscriptions', 'installments'])
            ->get();

        $subscriptions = $contracts
            ->flatMap(fn ($contract) => $contract->subscriptions->map(fn ($subscription) => [
                'subscription' => $subscription,
                'contract'     => $contract,
            ]))
            ->sortBy(fn ($item) => $item['subscription']->reference);

        $filename = 'account-' . $account->id . '-subscriptions-' . now()->format('Ymd-His') . '.xls';

        return $this->buildSpreadsheet($account, $subscriptions, $filename);
    }

    /**
     * Export subscriptions that must be cancelled on a given date.
     *
     * A subscription is included when its contract's EARLIEST early-cancellation
     * end_date matches the target date (the contract's original end_date
     * does not count — it is cancelled automatically at the bank).
     *
     * @param string|null $date Optional target month in "Y-m" format (e.g.
     *                          "2026-10"). When omitted, the next draw date
     *                          after the account's last lock is used.
     * @param array|null $branchIds Optional branch IDs to filter contracts by.
     */
    public function exportCancellations(Account $account, ?string $date = null, ?array $branchIds = null): StreamedResponse
    {
        $contracts = $account->installmentContracts()
            ->where('status', ContractStatus::CONFIGURED)
            ->when(! empty($branchIds), fn ($query) => $query->whereIn('branch_id', $branchIds))
            ->whereHas('earlyCancelations', function ($query) use ($account, $date) {
                $this->applyPeriodFilter($query, 'end_date', $account, $date);
            })
            ->with(['client', 'subscriptions', 'earlyCancelations'])
            ->get();

        $subscriptions = $contracts
            ->flatMap(fn ($contract) => $contract->subscriptions->map(fn ($subscription) => [
                'subscription' => $subscription,
                'contract'     => $contract,
            ]))
            ->sortBy(fn ($item) => $item['subscription']->reference);

        $filename = 'account-' . $account->id . '-cancellations-' . now()->format('Ymd-His') . '.xls';

        return $this->buildSpreadsheet($account, $subscriptions, $filename);
    }

    /**
     * Build the .xls spreadsheet from the flattened subscription list.
     *
     * @param  \Illuminate\Support\Collection<int, array>  $subscriptions
     */
    private function buildSpreadsheet(Account $account, $subscriptions, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $headers = [
            'CompteA',
            'cleA',
            'NOM',
            'PRENOM',
            'MontantVo',
            'CompteB',
            'CleB',
            'DateDebut',
            'DateFin',
            'DateCeation',
            'MoisTraite',
            'Nbrecheance',
            'JourPrel',
            'Reference',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;

        foreach ($subscriptions as $item) {
            $contract     = $item['contract'];
            $subscription = $item['subscription'];
            $client       = $contract->client;

            $firstDueDate = $contract->start_date;
            $lastDueDate  = $contract->end_date;

            $sheet->fromArray([
                $client?->ccp_number,
                $client?->ccp_key,
                $client?->firstname,
                $client?->lastname,
                $subscription->amount,
                $account->ccp_number,
                $account->ccp_key,
                $firstDueDate,
                $lastDueDate,
                $firstDueDate,
                null, // MoisTraite (K) — set explicitly below.
                $contract->months_count,
                $account->draw_day,
                $subscription->reference,
            ], null, 'A' . $row);

            // MoisTraite (K) is always 0 for now.
            $sheet->setCellValue('K' . $row, 0);

            $row++;
        }

        $lastRow = max($row - 1, 1);

        // Match the template's number formats per column.
        // Numeric columns: CompteA (A), cleA (B), CompteB (F), CleB (G),
        // MoisTraite (K), Nbrecheance (L), JourPrel (M).
        foreach (['A', 'B', 'F', 'G', 'K', 'L', 'M'] as $col) {
            $sheet->getStyle($col . '1:' . $col . $lastRow)
                ->getNumberFormat()
                ->setFormatCode('0');
        }

        // MontantVo (E) uses two decimals.
        $sheet->getStyle('E1:E' . $lastRow)
            ->getNumberFormat()
            ->setFormatCode('0.00');

        // Text columns: NOM (C), PRENOM (D), Reference (N).
        foreach (['C', 'D', 'N'] as $col) {
            $sheet->getStyle($col . '1:' . $col . $lastRow)
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_TEXT);
        }

        // Date columns: DateDebut (H), DateFin (I), DateCeation (J).
        $sheet->getStyle('H1:J' . $lastRow)
            ->getNumberFormat()
            ->setFormatCode('m/d/yyyy');

        return response()->streamDownload(
            function () use ($spreadsheet) {
                (new Xls($spreadsheet))->save('php://output');
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.ms-excel',
            ]
        );
    }

    /**
     * Apply the period filter to the given relation query.
     *
     * When an explicit "Y-m" month is provided, the column is filtered by that
     * month and year. Otherwise the column is matched against the next draw
     * date after the account's last lock.
     */
    private function applyPeriodFilter($query, string $column, Account $account, ?string $date): void
    {
        if ($date !== null) {
            $month = Carbon::parse($date);

            $query->whereYear($column, $month->year)
                ->whereMonth($column, $month->month);

            return;
        }

        $query->whereDate($column, $this->resolveTargetDrawDate($account));
    }

    /**
     * Resolve the target draw date for the export.
     *
     * The next draw date after the account's last lock date is generated from
     * the account's draw day.
     */
    private function resolveTargetDrawDate(Account $account): Carbon
    {
        $lastLock = $account->drawLocks()->latest('month')->first();

        $candidate = $lastLock !== null
            ? Carbon::parse($lastLock->month)->startOfDay()
            : now()->startOfDay();

        $month = $candidate->copy()->startOfMonth();

        if ($lastLock === null || $month->lte($candidate)) {
            $month = $candidate->copy()->addMonth()->startOfMonth();
        }

        $day = min($account->draw_day, $month->daysInMonth);

        return $month->copy()->addDays($day - 1);
    }
}
