<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expiration extends Model
{

    protected $fillable = [
        'user_id',
        'inventory_id',
        'reference',
        'note',
        'reported_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function scopeApproved(Builder $query): void
    {
        $query->whereNotNull('approved_at');
    }

    public function scopePending(Builder $query): void
    {
        $query->whereNull('approved_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpirationItem::class, 'expiration_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'moveable_id');
    }
}
