<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Traits\HasUserstamps;

class InventoryMovement extends Model
{
    use HasUserstamps;
    

    protected $fillable = [
        'stock_id',
        'inventory_id',
        'product_id',
        'source_id',
        'source_type',
        'movement_type',
        'old_quantity',
        'new_quantity',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => InventoryMovementType::class,
            'old_quantity'  => 'integer',
            'new_quantity'  => 'integer',
            'quantity'      => 'integer',
            'created_at'    => 'datetime',
        ];
    }

    public function scopeOfType(Builder $query, InventoryMovementType $type): void
    {
        $query->where('movement_type', $type);
    }

    public function scopeInbound(Builder $query): void
    {
        $query->whereIn('movement_type', [
            InventoryMovementType::RECEIVE,
            InventoryMovementType::TRANSFER_IN,
            InventoryMovementType::RESTOCK_RECEIVED,
            InventoryMovementType::TRANSFER_CANCEL,
            InventoryMovementType::SALE_RETURN,
        ]);
    }

    public function scopeOutbound(Builder $query): void
    {
        $query->whereIn('movement_type', [
            InventoryMovementType::RETURN,
            InventoryMovementType::TRANSFER_OUT,
            InventoryMovementType::SALE,
            InventoryMovementType::EXPIRED,
            InventoryMovementType::SALE_UPDATE,
        ]);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BatchAllocation::class);
    }
}
