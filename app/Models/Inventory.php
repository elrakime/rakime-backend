<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{

    protected $fillable = ['branch_id', 'name'];

    public function scopeCentral(Builder $query): void
    {
        $query->whereNull('branch_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function transfersOut(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_inventory_id');
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_inventory_id');
    }

    public function expirations(): HasMany
    {
        return $this->hasMany(ProductExpiration::class);
    }
}
