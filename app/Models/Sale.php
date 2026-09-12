<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasUserstamps;

class Sale extends Model
{
    use LogsActivity;
    use HasUserstamps;

    protected $fillable = [
        'branch_id',
        'client_id',
        'reference',
        'gross_amount',
        'tax_rate',
        'tax_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'total_amount',
        'purchase_cost',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount'    => 'decimal:2',
            'tax_rate'        => 'decimal:2',
            'tax_amount'      => 'decimal:2',
            'discount_value'  => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount'    => 'decimal:2',
            'purchase_cost'   => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function scopeByUserBranches(Builder $query): void
    {
        $user = auth()->user();

        if (! $user || $user->hasRole(Role::ADMIN->value)) {
            return;
        }

        $branchIds = $user->branches()->pluck('branch_id');

        if ($branchIds->isNotEmpty()) {
            $query->whereIn('branch_id', $branchIds);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'source_id');
    }

    /**
     * Recompute all derived amount columns from their sources of truth.
     *
     * - gross_amount     = Σ (sale_items.quantity * sale_items.price)
     * - tax_amount       = gross_amount * tax_rate / 100
     * - discount_amount  = per discount_type / discount_value
     * - total_amount     = gross_amount + tax_amount - discount_amount
     * - purchase_cost    = net FIFO cost from signed batch allocations
     *                      (SALE negative, SALE_UPDATE signed, SALE_RETURN
     *                      positive), so approved returns reduce the cost.
     */
    public function recalculateAmounts(): void
    {
        $grossAmount    = $this->grossAmount();
        $taxAmount      = $this->taxAmount($grossAmount);
        $discountAmount = $this->discountAmount($grossAmount);
        $totalAmount    = $grossAmount + $taxAmount - $discountAmount;

        $values = [
            'gross_amount'    => $grossAmount,
            'tax_amount'      => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount'    => $totalAmount,
            'purchase_cost'   => $this->purchaseCostAmount(),
        ];

        static::query()->whereKey($this->getKey())->update($values);

        $this->forceFill($values);
        $this->syncOriginal();
    }

    /**
     * Sum of (quantity * price) across the sale's line items.
     */
    private function grossAmount(): float
    {
        return (float) round((float) $this->items()->sum(DB::raw('quantity * price')), 2);
    }

    /**
     * Tax amount derived from the persisted tax_rate.
     */
    private function taxAmount(float $grossAmount): float
    {
        if (empty($this->tax_rate)) {
            return 0.0;
        }

        return (float) round($grossAmount * (float) $this->tax_rate / 100, 2);
    }

    /**
     * Discount amount derived from the persisted discount_type / discount_value.
     */
    private function discountAmount(float $grossAmount): float
    {
        if (empty($this->discount_type) || $this->discount_value === null) {
            return 0.0;
        }

        $discountValue = (float) $this->discount_value;

        if ($this->discount_type === DiscountType::PERCENTAGE->value) {
            return (float) round($grossAmount * $discountValue / 100, 2);
        }

        if ($this->discount_type === DiscountType::FIXED->value) {
            return $discountValue;
        }

        return 0.0;
    }

    /**
     * Sum of (quantity * purchase_price) across all batch allocations of the
     * sale's inventory movements. Signed quantities net returns automatically.
     */
    private function purchaseCostAmount(): float
    {
        $cost = $this->inventoryMovements()
            ->join('batch_allocations', 'batch_allocations.inventory_movement_id', '=', 'inventory_movements.id')
            ->sum(DB::raw('batch_allocations.quantity * batch_allocations.purchase_price'));

        return (float) round((float) $cost, 2);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference = '';
        });

        static::created(function (self $model) {
            $model->updateQuietly(['reference' => 'SAL-' . now()->format('Y') . '-' . str_pad((string) $model->id, 4, '0', STR_PAD_LEFT)]);
        });
    }
}
