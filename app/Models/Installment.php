<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InstallmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\HasStatusHistory;
use App\Traits\HasStatusGuard;
use App\Traits\HasUserstamps;

class Installment extends Model
{
    use HasStatusHistory;
    use HasStatusGuard;
    use HasUserstamps;

    protected $fillable = [
        'contract_id',
        'amount',
        'status',
        'payment_method',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'status'         => InstallmentStatus::class,
            'amount'         => 'decimal:2',
            'due_date'       => 'date',
        ];
    }

    public function scopeUnpaid(Builder $query): void
    {
        $query->where('status', InstallmentStatus::UNPAID);
    }

    public function scopePaid(Builder $query): void
    {
        $query->where('status', InstallmentStatus::PAID);
    }

    public function scopePartiallyPaid(Builder $query): void
    {
        $query->where('status', InstallmentStatus::PARTIALLY_PAID);
    }

    public function scopeBankMethod(Builder $query): void
    {
        $query->where('payment_method', 'BANK');
    }

    public function scopeCashMethod(Builder $query): void
    {
        $query->where('payment_method', 'CASH');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function cashPayment(): HasOne
    {
        return $this->hasOne(InstallmentPayment::class);
    }

    public function draws(): HasMany
    {
        return $this->hasMany(Draw::class);
    }
}
