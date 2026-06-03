<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class InstallmentContract extends Model
{
    use LogsActivity;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'client_id',
        'account_id',
        'branch_id',
        'reference',
        'status',
        'max_amount',
        'advance_amount',
        'months_count',
        'total_amount',
        'monthly_amount',
        'note',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'status'         => ContractStatus::class,
            'max_amount'     => 'integer',
            'advance_amount' => 'integer',
            'months_count'   => 'integer',
            'total_amount'   => 'integer',
            'monthly_amount' => 'integer',
            'confirmed_at'   => 'datetime',
            'created_at'     => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function scopeDraft(Builder $query): void
    {
        $query->where('status', ContractStatus::DRAFT);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', ContractStatus::PENDING);
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', ContractStatus::APPROVED);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', ContractStatus::ACTIVE);
    }

    public function scopeConfirmed(Builder $query): void
    {
        $query->where('status', ContractStatus::CONFIRMED);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', ContractStatus::COMPLETED);
    }

    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', ContractStatus::CANCELLED);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InstallmentContractItem::class, 'contract_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class, 'contract_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(InstallmentSubscription::class, 'contract_id');
    }
}
