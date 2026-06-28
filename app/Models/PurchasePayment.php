<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{

    protected $fillable = [
        'purchase_id',
        'amount',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'  => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
