<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FinancialRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class FinancialRecordService
{
    public function list(Request $request): LengthAwarePaginator
    {
        $query = FinancialRecord::query()->with(['client', 'contract']);

        if ($clientId = $request->integer('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($contractId = $request->integer('contract_id')) {
            $query->where('contract_id', $contractId);
        }

        return $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): FinancialRecord
    {
        $revenues = $this->normalizeAmounts($data['revenues'] ?? []);
        $expenses = $this->normalizeAmounts($data['expenses'] ?? []);

        $record = FinancialRecord::create([
            'client_id'   => $data['client_id'],
            'contract_id' => $data['contract_id'] ?? null,
            'revenues'    => $revenues,
            'expenses'    => $expenses,
            'income'      => $this->calculateIncome($revenues, $expenses),
            'note'        => $data['note'] ?? null,
        ]);

        return $record->load(['client', 'contract']);
    }

    public function update(FinancialRecord $financialRecord, array $data): FinancialRecord
    {
        $revenues = isset($data['revenues'])
            ? $this->normalizeAmounts($data['revenues'])
            : $financialRecord->revenues ?? [];

        $expenses = isset($data['expenses'])
            ? $this->normalizeAmounts($data['expenses'])
            : $financialRecord->expenses ?? [];

        $financialRecord->update([
            'revenues' => $revenues,
            'expenses' => $expenses,
            'income'   => $this->calculateIncome($revenues, $expenses),
            'note'     => $data['note'] ?? $financialRecord->note,
        ]);

        return $financialRecord->load(['client', 'contract']);
    }

    public function delete(FinancialRecord $financialRecord): void
    {
        $financialRecord->delete();
    }

    /**
     * Normalize the revenues/expenses array into [{amount, count}] entries.
     * Accepts both a plain number (count defaults to 1) and {amount, count} objects.
     */
    private function normalizeAmounts(array $amounts): array
    {
        return array_map(function ($item) {
            if (is_array($item)) {
                return [
                    'amount' => (float) ($item['amount'] ?? 0),
                    'count'  => (int) ($item['count'] ?? 1),
                ];
            }

            return [
                'amount' => (float) $item,
                'count'  => 1,
            ];
        }, $amounts);
    }

    private function calculateIncome(array $revenues, array $expenses): float
    {
        $totalRevenues = array_sum(array_map(
            fn ($item) => $item['amount'] * $item['count'],
            $revenues
        ));

        $totalExpenses = array_sum(array_map(
            fn ($item) => $item['amount'] * $item['count'],
            $expenses
        ));

        return round($totalRevenues - $totalExpenses, 2);
    }
}
