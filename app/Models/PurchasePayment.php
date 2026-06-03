<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchasePaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{

    protected $fillable = [
        'purchase_id',
        'amount',
        'payment_method',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PurchasePaymentMethod::class,
            'amount'         => 'integer',
            'paid_at'        => 'datetime',
        ];
    }

    public function scopeCash(Builder $query): void
    {
        $query->where('payment_method', PurchasePaymentMethod::CASH);
    }

    public function scopeBank(Builder $query): void
    {
        $query->where('payment_method', PurchasePaymentMethod::BANK);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
