<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Stock extends Model
{
    use LogsActivity;


    protected $fillable = [
        'inventory_id',
        'product_id',
        'source_id',
        'source_type',
        'initial_quantity',
        'current_quantity',
        'purchase_price',
        'selling_price',
        'installment_price',
        'purchased_at',
    ];

    protected function casts(): array
    {
        return [
            'initial_quantity'  => 'integer',
            'current_quantity'  => 'integer',
            'purchase_price'    => 'integer',
            'selling_price'     => 'integer',
            'installment_price' => 'integer',
            'purchased_at'      => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function scopeAvailable(Builder $query): void
    {
        $query->where('current_quantity', '>', 0);
    }

    public function scopeInInventory(Builder $query, int $inventoryId): void
    {
        $query->where('inventory_id', $inventoryId);
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
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function installmentContractItems(): HasMany
    {
        return $this->hasMany(InstallmentContractItem::class);
    }

    public function expirationItems(): HasMany
    {
        return $this->hasMany(ProductExpirationItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
