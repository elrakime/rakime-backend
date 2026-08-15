<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseReturnStatus;
use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasStatusHistory;
use App\Traits\HasStatusGuard;
use App\Traits\HasUserstamps;

class Purchase extends Model
{
    use LogsActivity;
    use HasStatusHistory;
    use HasStatusGuard;
    use HasUserstamps;


    protected $fillable = [
        'supplier_id',
        'branch_id',
        'inventory_id',
        'reference',
        'status',
        'total_amount',
        'paid_amount',
        'note',
    ];

    protected $appends = ['payment_status', 'net_amount'];

    protected function casts(): array
    {
        return [
            'status'        => PurchaseStatus::class,
            'total_amount'  => 'integer',
            'paid_amount'   => 'integer',
            'inventory_id'  => 'integer',
            'branch_id'     => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', PurchaseStatus::PENDING);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', PurchaseStatus::COMPLETED);
    }

    public function scopeCanceled(Builder $query): void
    {
        $query->where('status', PurchaseStatus::CANCELED);
    }

    /**
     * Computed payment status: unpaid, partially_paid, or paid.
     */
    public function getPaymentStatusAttribute(): string
    {
        if ($this->paid_amount <= 0) {
            return 'unpaid';
        }

        if ($this->paid_amount >= $this->net_amount) {
            return 'paid';
        }

        return 'partially_paid';
    }

    /**
     * Total amount after subtracting completed return amounts.
     */
    public function getNetAmountAttribute(): int
    {
        return $this->total_amount - $this->totalReturnsAmount();
    }

    private function totalReturnsAmount(): int
    {
        if ($this->relationLoaded('returns')) {
            return $this->returns
                ->filter(fn (PurchaseReturn $return) => $return->status === PurchaseReturnStatus::COMPLETED)
                ->sum(fn (PurchaseReturn $return) => $return->items->sum(
                    fn (PurchaseReturnItem $item) => $item->quantity * $item->purchaseItem->price
                ));
        }

        return (int) $this->returns()
            ->where('status', PurchaseReturnStatus::COMPLETED->value)
            ->with('items.purchaseItem')
            ->get()
            ->sum(fn (PurchaseReturn $return) => $return->items->sum(
                fn (PurchaseReturnItem $item) => $item->quantity * $item->purchaseItem->price
            ));
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PurchaseRefund::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function restocks(): MorphMany
    {
        return $this->morphMany(Restock::class, 'fulfilled_with');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'source_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference = '';
        });

        static::created(function (self $model) {
            $model->updateQuietly(['reference' => 'PUR-' . now()->format('Y') . '-' . str_pad((string) $model->id, 4, '0', STR_PAD_LEFT)]);
        });
    }
}
