<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DrawStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasStatusHistory;
use App\Traits\HasStatusGuard;
use App\Traits\HasUserstamps;

class Draw extends Model
{
    use HasStatusHistory;
    use HasStatusGuard;
    use HasUserstamps;

    protected $fillable = [
        'subscription_id',
        'installment_id',
        'amount',
        'status',
        'due_date',
        'last_attempted_at',
        'tax_amount',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status'            => DrawStatus::class,
            'amount'            => 'decimal:2',
            'due_date'          => 'date',
            'last_attempted_at' => 'date',
            'tax_amount'        => 'decimal:2',
            'metadata'          => 'array',
        ];
    }

    public function scopePaidOnTime(Builder $query): void
    {
        $query->where('status', DrawStatus::PAID_ON_TIME);
    }

    public function scopeLatePayment(Builder $query): void
    {
        $query->where('status', DrawStatus::LATE_PAYMENT);
    }

    public function scopePostponed(Builder $query): void
    {
        $query->where('status', DrawStatus::POSTPONED);
    }

    public function scopeFailed(Builder $query): void
    {
        $query->where('status', DrawStatus::FAILED);
    }

    public function scopeSettled(Builder $query): void
    {
        $query->whereIn('status', [DrawStatus::PAID_ON_TIME, DrawStatus::LATE_PAYMENT]);
    }

    public function scopeScheduledBefore(Builder $query, string $date): void
    {
        $query->where('due_date', '<=', $date);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }
}
