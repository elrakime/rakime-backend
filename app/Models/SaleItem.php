<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SaleReturnStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUserstamps;

class SaleItem extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'sale_id',
        'product_id',
        'stock_id',
        'quantity',
        'price',
    ];

    protected $appends = ['net_quantity'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price'    => 'integer',
        ];
    }

    protected function netQuantity(): Attribute
    {
        return Attribute::get(function () {
            $completedReturns = (int) $this->returnItems()
                ->whereHas('saleReturn', fn ($q) => $q->where('status', SaleReturnStatus::COMPLETED))
                ->sum('quantity');

            return $this->quantity - $completedReturns;
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
