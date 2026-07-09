<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasStatusHistory;
use App\Traits\HasUserstamps;

class Purchase extends Model
{
    use LogsActivity;
    use HasStatusHistory;
    use HasUserstamps;


    protected $fillable = [
        'supplier_id',
        'inventory_id',
        'reference',
        'status',
        'total_amount',
        'paid_amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status'        => PurchaseStatus::class,
            'total_amount'  => 'integer',
            'paid_amount'   => 'integer',
            'inventory_id'  => 'integer',
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

    public function scopeReceived(Builder $query): void
    {
        $query->where('status', PurchaseStatus::RECEIVED);
    }

    public function scopePaid(Builder $query): void
    {
        $query->where('status', PurchaseStatus::PAID);
    }

    public function scopePartiallyPaid(Builder $query): void
    {
        $query->where('status', PurchaseStatus::PARTIALLY_PAID);
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
