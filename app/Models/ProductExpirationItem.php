<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductExpirationItem extends Model
{

    protected $table = 'product_expiration_items';

    protected $fillable = [
        'expiration_id',
        'product_id',
        'stock_id',
        'quantity',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function expiration(): BelongsTo
    {
        return $this->belongsTo(ProductExpiration::class, 'expiration_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
