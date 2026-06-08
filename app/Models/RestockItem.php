<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestockItem extends Model
{

    protected $fillable = [
        'restock_order_id',
        'product_id',
        'requested_quantity',
        'fulfilled_quantity',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'integer',
            'fulfilled_quantity' => 'integer',
        ];
    }

    public function restockOrder(): BelongsTo
    {
        return $this->belongsTo(Restock::class, 'restock_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
