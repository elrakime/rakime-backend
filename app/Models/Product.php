<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    use LogsActivity;


    protected $fillable = [
        'type_id',
        'color_id',
        'brand_id',
        'name',
        'barcode',
        'min_quantity',
    ];

    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
        $this->addMediaCollection('gallery');
    }

    public function scopeLowStock(Builder $query): void
    {
        $query->whereHas('stocks', function (Builder $q) {
            $q->whereColumn('current_quantity', '<', 'products.min_quantity');
        });
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function transferItems(): HasMany
    {
        return $this->hasMany(TransferItem::class);
    }

    public function restockOrderItems(): HasMany
    {
        return $this->hasMany(RestockItem::class);
    }

    public function installmentContractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    public function expirationItems(): HasMany
    {
        return $this->hasMany(ExpirationItem::class);
    }
}
