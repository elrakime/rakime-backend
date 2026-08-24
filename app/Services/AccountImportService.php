<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class AccountImportService
{
    /**
     * Parse a bank return .txt file into an array of items.
     *
     * Each non-empty line is decoded using fixed-width fields in the
     * following order:
     *
     *  | field                     | length |
     *  |---------------------------|--------|
     *  | client ccp number         | 10     |
     *  | client fullname           | 27     |
     *  | amount                    | 14     |
     *  | date                      | 10     |
     *  | account ccp number        | 10     |
     *  | status code               | 1      |
     *  | offset                    | 2      |
     *  | subscription reference    | rest   |
     *
     * The amount is a fixed-width decimal formatted as `%14.2f` (e.g.
     * `000000001234.50`). The date is formatted as `dd/mm/yyyy`.
     *
     * In addition to the raw fields, each item is enriched with a
     * normalized `status`, a late-payment `tax`, and the resolved `cycle`
     * date derived from the account's draw day.
     *
     * @return array<int, array{
     *     client_ccp_number: string,
     *     client_fullname: string,
     *     amount: string,
     *     date: string,
     *     account_ccp_number: string,
     *     status_code: string,
     *     offset: string,
     *     subscription_reference: string,
     *     status: string,
     *     tax: int,
     *     cycle: string,
     * }>
     */
    public function import(UploadedFile $file, int $drawDay): array
    {
        $content = $file->get();

        if ($content === false || trim($content) === '') {
            throw new RuntimeException(__('account_imports.empty_file'), 422);
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);

        if ($lines === false) {
            throw new RuntimeException(__('account_imports.unreadable_file'), 422);
        }

        $items = [];

        foreach ($lines as $line) {
            $line = rtrim($line, "\r\n");

            if (trim($line) === '') {
                continue;
            }

            $items[] = $this->parseLine($line, $drawDay);
        }

        if ($items === []) {
            throw new RuntimeException(__('account_imports.empty_file'), 422);
        }

        return $items;
    }

    /**
     * Decode a single fixed-width line into its fields.
     *
     * @return array{
     *     client_ccp_number: string,
     *     client_fullname: string,
     *     amount: string,
     *     date: string,
     *     account_ccp_number: string,
     *     status_code: string,
     *     offset: string,
     *     subscription_reference: string,
     *     status: string,
     *     tax: int,
     *     cycle: string,
     * }
     */
    private function parseLine(string $line, int $drawDay): array
    {
        $amount       = trim(substr($line, 37, 14));
        $date         = trim(substr($line, 51, 10));
        $statusCode   = trim(substr($line, 71, 1));
        $offset       = trim(substr($line, 72, 2));

        $status = $this->resolveStatus($statusCode, $offset);

        return [
            'client_ccp_number'      => trim(substr($line, 0, 10)),
            'client_fullname'        => trim(substr($line, 10, 27)),
            'amount'                 => $amount,
            'date'                   => $date,
            'account_ccp_number'     => trim(substr($line, 61, 10)),
            'status_code'            => $statusCode,
            'offset'                 => $offset,
            'subscription_reference' => trim(substr($line, 74)),
            'status'                 => $status,
            'tax'                    => $this->resolveTax($status, $amount),
            'cycle'                  => $this->resolveCycle($date, $offset, $drawDay),
        ];
    }

    /**
     * Normalize the raw status code + offset into a status string.
     */
    private function resolveStatus(string $statusCode, string $offset): string
    {
        if ($statusCode === '1') {
            return 'postponed';
        }

        if ($statusCode !== '0') {
            return 'failed';
        }

        return $offset === '00' ? 'paid_on_time' : 'late_payment';
    }

    /**
     * Compute the 5% late-payment tax. Only late payments are taxed.
     */
    private function resolveTax(string $status, string $amount): int
    {
        if ($status !== 'late_payment') {
            return 0;
        }

        return (int) round(((float) $amount) * 0.05);
    }

    /**
     * Resolve the cycle date closest on or before the estimated due date.
     *
     * The estimated due date is `date - offset` days. Candidate cycle dates
     * are the draw day of the current and previous month (clamped to the
     * month's length). The latest candidate that is on or before the due
     * date is returned, formatted as `Y-m-d`.
     */
    private function resolveCycle(string $date, string $offset, int $drawDay): string
    {
        $dueDate = Carbon::createFromFormat('d/m/Y', $date)->startOfDay();

        if ($dueDate === false) {
            return '';
        }

        $dueDate->subDays((int) $offset);

        $candidates = [
            $this->cycleDate($dueDate->copy()->subMonth(), $drawDay),
            $this->cycleDate($dueDate, $drawDay),
        ];

        $cycle = null;

        foreach ($candidates as $candidate) {
            if ($candidate->lte($dueDate) && ($cycle === null || $candidate->gt($cycle))) {
                $cycle = $candidate;
            }
        }

        return $cycle?->format('Y-m-d') ?? '';
    }

    /**
     * Build the cycle date for the given month using the account draw day,
     * clamping the day to the month's length.
     */
    private function cycleDate(Carbon $month, int $drawDay): Carbon
    {
        $date = $month->copy()->startOfMonth();
        $day  = min($drawDay, $date->daysInMonth);

        return $date->setDay($day);
    }
}
