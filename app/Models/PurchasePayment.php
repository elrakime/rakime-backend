<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUserstamps;

class PurchasePayment extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'purchase_id',
        'amount',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'integer',
            'canceled_at' => 'datetime',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
