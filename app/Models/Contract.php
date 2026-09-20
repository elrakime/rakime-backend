<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\DrawStatus;
use App\Enums\InstallmentStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasStatusHistory;
use App\Traits\HasStatusGuard;
use App\Traits\HasUserstamps;

class Contract extends Model
{
    use LogsActivity;
    use HasStatusHistory;
    use HasStatusGuard;
    use HasUserstamps;

    protected $fillable = [
        'parent_contract_id',
        'extended_at',
        'client_id',
        'account_id',
        'branch_id',
        'reference',
        'status',
        'max_amount',
        'advance_amount',
        'months_count',
        'total_amount',
        'net_amount',
        'monthly_amount',
        'purchase_cost',
        'start_date',
        'end_date',
        'note',
    ];

    protected $appends = ['payment_status'];

    protected function casts(): array
    {
        return [
            'status'         => ContractStatus::class,
            'max_amount'     => 'decimal:2',
            'advance_amount' => 'decimal:2',
            'months_count'   => 'integer',
            'total_amount'   => 'decimal:2',
            'net_amount'     => 'decimal:2',
            'monthly_amount' => 'decimal:2',
            'purchase_cost'  => 'decimal:2',
            'start_date'     => 'date',
            'end_date'       => 'date',
            'extended_at'    => 'datetime',
            'created_at'     => 'datetime',
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

    public function scopePending(Builder $query): void
    {
        $query->where('status', ContractStatus::PENDING);
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', ContractStatus::APPROVED);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', ContractStatus::ACTIVE);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', ContractStatus::COMPLETED);
    }

    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', ContractStatus::CANCELLED);
    }

    public function scopeConfigured(Builder $query): void
    {
        $query->where('status', ContractStatus::CONFIGURED);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class, 'contract_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'contract_id');
    }

    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'source_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ContractPayment::class, 'contract_id');
    }

    public function earlyCancelations(): HasMany
    {
        return $this->hasMany(ContractEarlyCancelation::class, 'contract_id');
    }

    public function parentContract(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_contract_id');
    }

    public function extension(): HasOne
    {
        return $this->hasOne(self::class, 'parent_contract_id');
    }

    public function isExtension(): bool
    {
        return $this->parent_contract_id !== null;
    }

    public function isSuperseded(): bool
    {
        return $this->extension()->exists();
    }

    /**
     * Total amount already paid on this contract.
     *
     * Sums settled draws (paid on time or late) and cash payments. These two
     * sources are mutually exclusive per installment, so summing both is safe.
     */
    public function paidAmount(): float
    {
        $drawsPaid = (float) $this->draws()
            ->whereIn('status', [DrawStatus::PAID_ON_TIME->value, DrawStatus::LATE_PAYMENT->value])
            ->sum('amount');

        $cashPaid = (float) $this->payments()->sum('amount');

        return $drawsPaid + $cashPaid;
    }

    public function draws(): HasManyThrough
    {
        return $this->hasManyThrough(Draw::class, Subscription::class, 'contract_id', 'subscription_id');
    }

    /**
     * Derived payment status based on the installments of this contract.
     *
     * - paid           : all installments are paid
     * - unpaid         : all installments are unpaid
     * - partially_paid : any other combination
     */
    public function getPaymentStatusAttribute(): string
    {
        $installments = $this->relationLoaded('installments')
            ? $this->installments
            : $this->installments()->get();

        if ($installments->isEmpty()) {
            return InstallmentStatus::UNPAID->value;
        }

        $paidCount   = $installments->where('status', InstallmentStatus::PAID)->count();
        $unpaidCount = $installments->where('status', InstallmentStatus::UNPAID)->count();
        $total       = $installments->count();

        if ($paidCount === $total) {
            return InstallmentStatus::PAID->value;
        }

        if ($unpaidCount === $total) {
            return InstallmentStatus::UNPAID->value;
        }

        return InstallmentStatus::PARTIALLY_PAID->value;
    }

    /**
     * Recompute the derived amount columns from their sources of truth.
     *
     * - total_amount   = Σ (contract_items.quantity * contract_items.price)
     * - net_amount     = total_amount - advance_amount
     * - monthly_amount = ceil(net_amount / months_count)
     *
     * Each is nullable when its inputs are absent (e.g. no items, no
     * months_count), matching the original ContractService::recalculateAmounts().
     */
    public function recalculateAmounts(): void
    {
        $items = $this->items()->get();

        $totalAmount = $items->isEmpty()
            ? null
            : (float) $items->sum(fn ($item) => $item->quantity * $item->price);

        $advanceAmount = (float) ($this->advance_amount ?? 0);
        $monthsCount   = $this->months_count;

        $netAmount = $totalAmount !== null
            ? $totalAmount - $advanceAmount
            : null;

        $monthlyAmount = ($netAmount !== null && $monthsCount > 0)
            ? (float) ceil($netAmount / $monthsCount)
            : null;

        $values = [
            'total_amount'   => $totalAmount,
            'net_amount'     => $netAmount,
            'monthly_amount' => $monthlyAmount,
            'purchase_cost'  => $this->purchaseCostAmount(),
        ];

        static::query()->whereKey($this->getKey())->update($values);

        $this->forceFill($values);
        $this->syncOriginal();
    }

    /**
     * Sum of (quantity * purchase_price) across all batch allocations of the
     * contract's inventory movements. Signed quantities net returns automatically.
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
            $branchCode = $model->branch?->code ?? '';
            $year = now()->format('y');

            $model->updateQuietly([
                'reference' => $branchCode . $year . '-' . str_pad((string) $model->id, 5, '0', STR_PAD_LEFT),
            ]);
        });
    }
}
