<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\InstallmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(ContractPayment::class, 'installment_payments')
            ->withTimestamps();
    }

    public function draws(): HasMany
    {
        return $this->hasMany(Draw::class);
    }

    protected static function booted(): void
    {
        static::updated(function (self $installment) {
            if (! $installment->wasChanged('status')) {
                return;
            }

            if ($installment->status !== InstallmentStatus::PAID) {
                return;
            }

            $contract = $installment->contract;

            if ($contract === null || $contract->status !== ContractStatus::ACTIVE) {
                return;
            }

            $allPaid = $contract->installments()
                ->where('status', '!=', InstallmentStatus::PAID)
                ->doesntExist();

            if ($allPaid) {
                $contract->update(['status' => ContractStatus::COMPLETED]);
            }
        });
    }
}
