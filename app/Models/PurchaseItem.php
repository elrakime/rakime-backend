<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseReturnStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Traits\HasUserstamps;

class PurchaseItem extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'price',
    ];

    protected $appends = ['net_quantity'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price'    => 'decimal:2',
        ];
    }

    protected function netQuantity(): Attribute
    {
        return Attribute::get(function () {
            if ($this->relationLoaded('returnItems')) {
                return $this->quantity - $this->returnItems
                    ->filter(fn ($ri) => $ri->relationLoaded('purchaseReturn')
                        && $ri->purchaseReturn->status === PurchaseReturnStatus::COMPLETED->value)
                    ->sum('quantity');
            }

            return $this->quantity - (int) $this->returnItems()
                ->whereHas('purchaseReturn', fn ($q) => $q->where('status', PurchaseReturnStatus::COMPLETED))
                ->sum('quantity');
        });
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): MorphOne
    {
        return $this->morphOne(Stock::class, 'source', 'source_type', 'source_id');
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
