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
     * @param string|null $drawDate Optional target draw date. When omitted, the
     *                              next draw date after the account's last lock
     *                              is used.
     */
    public function export(Account $account, ?string $drawDate = null): StreamedResponse
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

        $targetDrawDate = $this->resolveTargetDrawDate($account, $drawDate);

        $contracts = $account->installmentContracts()
            ->where('status', ContractStatus::CONFIGURED)
            ->whereHas('installments', function ($query) use ($targetDrawDate) {
                $query->whereDate('due_date', $targetDrawDate);
            })
            ->with(['client', 'subscriptions', 'installments'])
            ->get();

        foreach ($contracts as $contract) {
            $client = $contract->client;

            $installments = $contract->installments->sortBy('due_date');

            $firstDueDate = $installments->first()?->due_date;
            $lastDueDate  = $installments->last()?->due_date;

            foreach ($contract->subscriptions as $subscription) {
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

        $filename = 'account-' . $account->id . '-subscriptions-' . now()->format('Ymd-His') . '.xls';

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
     * Resolve the target draw date for the export.
     *
     * When an explicit draw date is provided it is used as-is. Otherwise the
     * next draw date after the account's last lock date is generated from the
     * account's draw day.
     */
    private function resolveTargetDrawDate(Account $account, ?string $drawDate): Carbon
    {
        if ($drawDate !== null) {
            return Carbon::parse($drawDate)->startOfDay();
        }

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
