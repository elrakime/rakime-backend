<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\InventoryMovementType;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Price;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Wallet;
use App\Traits\ScopesByUserBranches;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class SaleService
{
    use ScopesByUserBranches;

    public function __construct(private readonly WalletService $walletService) {}
    public function list(Request $request): LengthAwarePaginator
    {
        $query = Sale::query();

        $this->scopeByUserBranches($query);

        return QueryBuilder::for($query, $request)
            ->with(['user', 'branch', 'client', 'items.product', 'items.stock'])
            ->allowedFilters(
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('client_id'),
                AllowedFilter::partial('reference'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                          ->orWhere('note', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('reference'),
                AllowedSort::field('total_amount'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $inventory = $this->getBranchInventory($data['branch_id']);
            $resolvedItems = $this->resolveItems($inventory->id, $data['items']);

            $grossAmount = collect($resolvedItems)->sum(fn ($item) => $item['quantity'] * $item['price']);

            [$taxAmount, $discountAmount, $totalAmount] = $this->calculateTotals($grossAmount, $data);

            $sale = Sale::create([
                'branch_id'       => $data['branch_id'],
                'client_id'       => $data['client_id'] ?? null,
                'gross_amount'    => $grossAmount,
                'tax_rate'        => $data['tax_rate'] ?? null,
                'tax_amount'      => $taxAmount,
                'discount_type'   => $data['discount_type'] ?? null,
                'discount_value'  => $data['discount_value'] ?? null,
                'discount_amount' => $discountAmount,
                'total_amount'    => $totalAmount,
                'note'            => $data['note'] ?? null,
            ]);

            foreach ($resolvedItems as $item) {
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item['product_id'],
                    'stock_id'   => $item['stock_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);
            }

            $this->deductStock($sale);

            $this->creditBranchWallet($data['branch_id'], $totalAmount, $sale);

            return $sale->load(['user', 'branch', 'client', 'items.product', 'items.stock']);
        });
    }

    public function show(Sale $sale): Sale
    {
        return $sale->loadMissing(['user', 'branch', 'client', 'items.product', 'items.stock']);
    }

    public function update(Sale $sale, array $data): Sale
    {
        $updateData = [
            'branch_id' => $data['branch_id'] ?? $sale->branch_id,
            'client_id' => $data['client_id'] ?? $sale->client_id,
            'note'      => $data['note'] ?? $sale->note,
        ];

        if (array_key_exists('tax_rate', $data) || array_key_exists('discount_type', $data) || array_key_exists('discount_value', $data)) {
            $taxRate       = $data['tax_rate'] ?? $sale->tax_rate;
            $discountType  = $data['discount_type'] ?? $sale->discount_type;
            $discountValue = $data['discount_value'] ?? $sale->discount_value;

            [$taxAmount, $discountAmount, $totalAmount] = $this->calculateTotals(
                $sale->gross_amount,
                [
                    'tax_rate'       => $taxRate,
                    'discount_type'  => $discountType,
                    'discount_value' => $discountValue,
                ],
            );

            $updateData['tax_rate']        = $taxRate;
            $updateData['tax_amount']      = $taxAmount;
            $updateData['discount_type']   = $discountType;
            $updateData['discount_value']  = $discountValue;
            $updateData['discount_amount'] = $discountAmount;
            $updateData['total_amount']    = $totalAmount;
        }

        $sale->update($updateData);

        return $sale->fresh()->loadMissing(['user', 'branch', 'client', 'items.product', 'items.stock']);
    }

    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $sale->items()->delete();
            $sale->inventoryMovements()->delete();
            $sale->delete();
        });
    }

    private function creditBranchWallet(int $branchId, int $amount, Sale $sale): void
    {
        $wallet = Wallet::where('owner_type', Branch::class)
            ->where('owner_id', $branchId)
            ->first();

        if (!$wallet) {
            return;
        }

        $this->walletService->salePayment(
            wallet: $wallet,
            amount: $amount,
            source: $sale,
            note: $sale->note,
        );
    }

    private function deductStock(Sale $sale): void
    {
        $sale->loadMissing('items');

        foreach ($sale->items as $item) {
            $remaining = $item->quantity;

            $batches = Batch::with('stock')
                ->where('stock_id', $item->stock_id)
                ->where('current_quantity', '>', 0)
                ->orderBy('created_at')
                ->get();

            $oldQuantity = $batches->sum('current_quantity');

            foreach ($batches as $batch) {
                $deduct = min($remaining, $batch->current_quantity);
                $batch->decrement('current_quantity', $deduct);

                $remaining -= $deduct;
                if ($remaining <= 0) {
                    break;
                }
            }

            $firstBatch = $batches->first();
            InventoryMovement::create([
                'stock_id'      => $item->stock_id,
                'inventory_id'  => $firstBatch?->stock->inventory_id,
                'product_id'    => $item->product_id,
                'source_id'     => $sale->id,
                'source_type'   => Sale::class,
                'movement_type' => InventoryMovementType::SALE,
                'old_quantity'  => $oldQuantity,
                'new_quantity'  => $oldQuantity - $item->quantity,
                'quantity'      => $item->quantity,
            ]);
        }
    }

    private function calculateTotals(int $grossAmount, array $data): array
    {
        $taxAmount = 0;
        $discountAmount = 0;

        if (!empty($data['tax_rate'])) {
            $taxAmount = (int) round($grossAmount * (int) $data['tax_rate'] / 100);
        }

        if (!empty($data['discount_type']) && isset($data['discount_value'])) {
            $discountValue = (int) $data['discount_value'];

            if ($data['discount_type'] === DiscountType::PERCENTAGE->value) {
                $discountAmount = (int) round($grossAmount * $discountValue / 100);
            } elseif ($data['discount_type'] === DiscountType::FIXED->value) {
                $discountAmount = $discountValue;
            }
        }

        $totalAmount = $grossAmount + $taxAmount - $discountAmount;

        return [$taxAmount, $discountAmount, $totalAmount];
    }

    private function getBranchInventory(int $branchId): Inventory
    {
        $inventory = Inventory::where('branch_id', $branchId)->first();

        if (!$inventory) {
            throw new Exception(__('sales.no_inventory_for_branch'), 422);
        }

        return $inventory;
    }

    /**
     * Validates that each stock belongs to the branch inventory and has sufficient quantity,
     * then resolves product_id and price.
     *
     * If price_id is provided, uses that specific price; otherwise falls back to the
     * stock's latest selling price.
     *
     * @return array[] items enriched with product_id and price
     */
    private function resolveItems(int $inventoryId, array $items): array
    {
        $stockIds = array_column($items, 'stock_id');
        $priceIds = array_filter(array_column($items, 'price_id'));

        $stocks = Stock::where('inventory_id', $inventoryId)
            ->whereIn('id', $stockIds)
            ->withSum('batches as total_current', 'current_quantity')
            ->with('sellingPrice')
            ->get()
            ->keyBy('id');

        $prices = [];
        if (!empty($priceIds)) {
            $prices = Price::whereIn('id', $priceIds)->get()->keyBy('id');
        }

        $resolved = [];

        foreach ($items as $item) {
            $stockId = $item['stock_id'];

            if (!isset($stocks[$stockId])) {
                throw new Exception(
                    __('sales.stock_not_in_branch_inventory', ['stock' => $stockId]), 422
                );
            }

            $stock = $stocks[$stockId];

            if (($stock->total_current ?? 0) < $item['quantity']) {
                throw new Exception(
                    __('sales.insufficient_stock', ['stock' => $stockId, 'available' => $stock->total_current ?? 0]), 422
                );
            }

            $price = isset($item['price_id'])
                ? $prices[$item['price_id']]->amount ?? 0
                : $stock->sellingPrice?->amount ?? 0;

            $resolved[] = [
                'stock_id'   => $stockId,
                'product_id' => $stock->product_id,
                'quantity'   => $item['quantity'],
                'price'      => $price,
            ];
        }

        return $resolved;
    }

}
