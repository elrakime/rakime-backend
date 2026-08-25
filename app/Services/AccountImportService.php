<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\DrawStatus;
use App\Enums\InstallmentPaymentMethod;
use App\Enums\InstallmentStatus;
use App\Models\Draw;
use App\Models\Installment;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
     *     tax: string,
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
     *     tax: string,
     *     cycle: string,
     * }
     */
    private function parseLine(string $line, int $drawDay): array
    {
        $amount       = number_format((float) trim(substr($line, 37, 14)), 2, '.', '');
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
    private function resolveTax(string $status, string $amount): string
    {
        if ($status !== 'late_payment') {
            return '0.00';
        }

        return number_format((float) $amount * 0.05, 2, '.', '');
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

    /**
     * Persist parsed import items as draws, linking each to its subscription
     * (by reference) and installment (by cycle == due_date).
     *
     * Exact duplicates (same subscription + installment + status +
     * last_attempted_at + tax) are skipped. A later successful payment for a
     * cycle whose draw already failed creates a NEW draw (failed is terminal).
     *
     * After processing, each affected installment's status is recomputed from
     * its draws, and contracts whose installments are all paid are completed.
     *
     * @param  array<int, array<string, string>>  $items  Parsed items from import().
     * @return array{created: int, skipped: int, failed: int, errors: array<int, string>}
     */
    public function process(array $items): array
    {
        $created = 0;
        $skipped = 0;
        $failed  = 0;
        $errors  = [];
        $touchedInstallmentIds = [];

        foreach ($items as $item) {
            try {
                $subscription = Subscription::where('reference', $item['subscription_reference'])->first();

                if ($subscription === null) {
                    throw new RuntimeException(__('account_imports.subscription_not_found', ['reference' => $item['subscription_reference']]));
                }

                $installment = Installment::where('contract_id', $subscription->contract_id)
                    ->whereDate('due_date', $item['cycle'])
                    ->first();

                if ($installment === null) {
                    throw new RuntimeException(__('account_imports.installment_not_found', ['cycle' => $item['cycle']]));
                }

                $status = DrawStatus::tryFrom($item['status']);

                if ($status === null) {
                    throw new RuntimeException(__('account_imports.invalid_status', ['status' => $item['status']]));
                }

                $lastAttemptedAt = Carbon::createFromFormat('d/m/Y', $item['date'])->startOfDay();

                if ($lastAttemptedAt === false) {
                    throw new RuntimeException(__('account_imports.invalid_date', ['date' => $item['date']]));
                }

                $duplicate = Draw::where('subscription_id', $subscription->id)
                    ->where('installment_id', $installment->id)
                    ->where('status', $status)
                    ->whereDate('last_attempted_at', $lastAttemptedAt)
                    ->where('tax_amount', $item['tax'])
                    ->exists();

                if ($duplicate) {
                    $skipped++;
                    $touchedInstallmentIds[$installment->id] = true;
                    continue;
                }

                Draw::create([
                    'subscription_id'   => $subscription->id,
                    'installment_id'    => $installment->id,
                    'amount'            => $item['amount'],
                    'status'            => $status,
                    'due_date'          => $item['cycle'],
                    'last_attempted_at' => $lastAttemptedAt,
                    'tax_amount'        => $item['tax'],
                    'metadata'          => $item,
                ]);

                $created++;
                $touchedInstallmentIds[$installment->id] = true;
            } catch (RuntimeException $e) {
                $failed++;
                $errors[] = $e->getMessage();
            }
        }

        if ($touchedInstallmentIds !== []) {
            $this->recalculateInstallments(array_keys($touchedInstallmentIds));
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'failed'  => $failed,
            'errors'  => $errors,
        ];
    }

    /**
     * Recompute the status of each touched installment from its draws, then
     * mark any fully-paid contract as completed.
     *
     * @param  array<int, int>  $installmentIds
     */
    private function recalculateInstallments(array $installmentIds): void
    {
        $contractIds = [];

        DB::transaction(function () use ($installmentIds, &$contractIds) {
            foreach ($installmentIds as $installmentId) {
                $installment = Installment::with('draws')->find($installmentId);

                if ($installment === null) {
                    continue;
                }

                $draws        = $installment->draws;
                $settledCount = $draws->whereIn('status', [DrawStatus::PAID_ON_TIME->value, DrawStatus::LATE_PAYMENT->value])->count();
                $totalDraws   = $draws->count();

                $status = match (true) {
                    $totalDraws === 0             => InstallmentStatus::UNPAID,
                    $settledCount === $totalDraws => InstallmentStatus::PAID,
                    $settledCount > 0             => InstallmentStatus::PARTIALLY_PAID,
                    default                       => InstallmentStatus::UNPAID,
                };

                $installment->update([
                    'status' => $status,
                ]);

                if ($status === InstallmentStatus::PAID) {
                    $installment->update([
                        'payment_method' => InstallmentPaymentMethod::BANK->value,
                    ]);
                }

                $contractIds[$installment->contract_id] = true;
            }
        });

        foreach (array_keys($contractIds) as $contractId) {
            $this->completeContractIfFullyPaid($contractId);
        }
    }

    /**
     * Mark a contract as completed when all of its installments are paid.
     */
    private function completeContractIfFullyPaid(int $contractId): void
    {
        $contract = \App\Models\Contract::with('installments')->find($contractId);

        if ($contract === null) {
            return;
        }

        $installments = $contract->installments;

        if ($installments->isEmpty()) {
            return;
        }

        $allPaid = $installments->every(fn ($installment) => $installment->status === InstallmentStatus::PAID);

        if ($allPaid && $contract->status !== ContractStatus::COMPLETED) {
            $contract->update(['status' => ContractStatus::COMPLETED]);
        }
    }
}
