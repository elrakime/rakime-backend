<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\Role;
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
        'draw_date',
        'total_amount',
        'net_amount',
        'monthly_amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status'         => ContractStatus::class,
            'max_amount'     => 'decimal:2',
            'advance_amount' => 'decimal:2',
            'months_count'   => 'integer',
            'draw_date'      => 'date',
            'total_amount'   => 'decimal:2',
            'net_amount'     => 'decimal:2',
            'monthly_amount' => 'decimal:2',
            'created_at'     => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function scopeByUserBranches(Builder $query): void
    {
        $user = auth()->user();

        if (! $user || $user->hasRole(Role::ADMIN->value)) {
            return;
        }

        $branchIds = $user->branches()->pluck('branch_id');

        if ($branchIds->isNotEmpty()) {
            $query->whereIn('branch_id', $branchIds);
        }
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

    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference = '';
        });

        static::created(function (self $model) {
            $branchCode = $model->branch?->code ?? '';
            $year = now()->format('y');

            $model->updateQuietly([
                'reference' => $branchCode . $year . '-' . str_pad((string) $model->id, 5, '0', STR_PAD_LEFT),
            ]);
        });
    }
}
