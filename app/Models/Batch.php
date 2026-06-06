<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Batch extends Model
{
    protected $fillable = [
        'stock_id',
        'source_id',
        'source_type',
        'purchase_price',
        'initial_quantity',
        'current_quantity',
        'purchased_at',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price'   => 'integer',
            'initial_quantity' => 'integer',
            'current_quantity' => 'integer',
            'purchased_at'     => 'datetime',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }
}
