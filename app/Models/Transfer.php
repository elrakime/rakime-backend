<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Transfer extends Model
{
    use LogsActivity;


    protected $fillable = [
        'from_inventory_id',
        'to_inventory_id',
        'note',
        'transferred_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'transferred_at' => 'datetime',
            'received_at'    => 'datetime',
        ];
    }

    public function scopeReceived($query): void
    {
        $query->whereNotNull('received_at');
    }

    public function scopePending($query): void
    {
        $query->whereNull('received_at');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function fromInventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'from_inventory_id');
    }

    public function toInventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'to_inventory_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferItem::class);
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
