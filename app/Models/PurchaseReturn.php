<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{

    protected $fillable = [
        'purchase_id',
        'reference',
        'returned_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function scopeApproved($query): void
    {
        $query->whereNotNull('approved_at');
    }

    public function scopePending($query): void
    {
        $query->whereNull('approved_at');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'moveable_id');
    }
}
