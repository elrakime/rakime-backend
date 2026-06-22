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

class Purchase extends Model
{
    use LogsActivity;


    protected $fillable = [
        'supplier_id',
        'reference',
        'status',
        'total_amount',
        'paid_amount',
        'note',
        'purchased_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'status'       => PurchaseStatus::class,
            'total_amount' => 'integer',
            'paid_amount'  => 'integer',
            'purchased_at' => 'datetime',
            'received_at'  => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function scopeDraft(Builder $query): void
    {
        $query->where('status', PurchaseStatus::DRAFT);
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
        return $this->hasMany(InventoryMovement::class, 'moveable_id');
    }
}
