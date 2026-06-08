<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PriceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Stock extends Model
{
    use LogsActivity;


    protected $fillable = [
        'inventory_id',
        'product_id',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function sellingPrice(): HasOne
    {
        return $this->hasOne(Price::class)->where('type', PriceType::SELLING)->latest();
    }

    public function installmentPrice(): HasOne
    {
        return $this->hasOne(Price::class)->where('type', PriceType::INSTALLMENT)->latest();
    }

    public function wholesalePrice(): HasOne
    {
        return $this->hasOne(Price::class)->where('type', PriceType::WHOLESALE)->latest();
    }

    public function currentQuantity(): HasOne
    {
        return $this->hasOne(Batch::class)
            ->selectRaw('stock_id, sum(current_quantity) as total_current_quantity')
            ->groupBy('stock_id');
    }

    public function initialQuantity(): HasOne
    {
        return $this->hasOne(Batch::class)
            ->selectRaw('stock_id, sum(initial_quantity) as total_initial_quantity')
            ->groupBy('stock_id');
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
