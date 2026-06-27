<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    

    protected $fillable = [
        'stock_id',
        'batch_id',
        'inventory_id',
        'product_id',
        'source_id',
        'movement_type',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => InventoryMovementType::class,
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
        ]);
    }

    public function scopeOutbound(Builder $query): void
    {
        $query->whereIn('movement_type', [
            InventoryMovementType::RETURN,
            InventoryMovementType::TRANSFER_OUT,
            InventoryMovementType::SALE,
            InventoryMovementType::EXPIRED,
        ]);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Resolve the polymorphic moveable based on movement_type.
     * No type column exists in DB; movement_type determines the model.
     */
    public function getSource(): ?Model
    {
        return match ($this->movement_type) {
            InventoryMovementType::RECEIVE          => Purchase::find($this->source_id),
            InventoryMovementType::RETURN           => PurchaseReturn::find($this->source_id),
            InventoryMovementType::TRANSFER_IN,
            InventoryMovementType::TRANSFER_OUT     => InventoryTransfer::find($this->source_id),
            InventoryMovementType::SALE             => Sale::find($this->source_id),
            InventoryMovementType::EXPIRED          => Expiration::find($this->source_id),
            InventoryMovementType::RESTOCK_RECEIVED => Restock::find($this->source_id),
            default                                 => null,
        };
    }
}
