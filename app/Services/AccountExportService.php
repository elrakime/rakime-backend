<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Models\Account;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountExportService
{
    /**
     * Export all configured contracts' subscriptions for the given account
     * as an Excel (.xls) spreadsheet.
     */
    public function export(Account $account): StreamedResponse
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

        $contracts = $account->installmentContracts()
            ->where('status', ContractStatus::CONFIGURED)
            ->with(['client', 'subscriptions.draws'])
            ->get();

        foreach ($contracts as $contract) {
            $client = $contract->client;

            foreach ($contract->subscriptions as $subscription) {
                $draws = $subscription->draws->sortBy('scheduled_date');

                $firstDraw = $draws->first()?->scheduled_date;
                $lastDraw  = $draws->last()?->scheduled_date;

                $sheet->fromArray([
                    $client?->ccp_number,
                    $client?->ccp_key,
                    $client?->firstname,
                    $client?->lastname,
                    $subscription->amount,
                    $account->ccp_number,
                    $account->ccp_key,
                    $firstDraw,
                    $lastDraw,
                    $firstDraw,
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
}
