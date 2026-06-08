<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractItem extends Model
{

    protected $fillable = [
        'contract_id',
        'product_id',
        'stock_id',
        'batch_id',
        'price_id',
        'quantity',
        'unit_price',
        'unit_price_snapshot',
        'installment_price_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity'                   => 'integer',
            'unit_price'                 => 'integer',
            'unit_price_snapshot'        => 'integer',
            'installment_price_snapshot' => 'integer',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class);
    }
}
