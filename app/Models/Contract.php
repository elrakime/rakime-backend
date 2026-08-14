<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasStatusHistory;
use App\Traits\HasStatusGuard;
use App\Traits\HasUserstamps;

class Contract extends Model
{
    use LogsActivity;
    use HasStatusHistory;
    use HasStatusGuard;
    use HasUserstamps;

    protected $fillable = [
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
            'created_at'     => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
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

    public function scopeConfigured(Builder $query): void
    {
        $query->where('status', ContractStatus::CONFIGURED);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
        return $this->hasMany(ContractItem::class, 'contract_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class, 'contract_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'contract_id');
    }
}
