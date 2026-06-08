<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InstallmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Installment extends Model
{

    protected $fillable = [
        'contract_id',
        'month_number',
        'amount',
        'status',
        'payment_method',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'status'         => InstallmentStatus::class,
            'month_number'   => 'integer',
            'amount'         => 'integer',
            'due_date'       => 'date',
        ];
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', InstallmentStatus::PENDING);
    }

    public function scopePaid(Builder $query): void
    {
        $query->where('status', InstallmentStatus::PAID);
    }

    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', InstallmentStatus::OVERDUE);
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
