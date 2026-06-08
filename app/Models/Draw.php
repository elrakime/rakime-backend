<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DrawStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Draw extends Model
{

    protected $fillable = [
        'subscription_id',
        'installment_id',
        'month_number',
        'amount',
        'status',
        'scheduled_date',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'status'         => DrawStatus::class,
            'month_number'   => 'integer',
            'amount'         => 'integer',
            'scheduled_date' => 'date',
            'processed_at'   => 'datetime',
        ];
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', DrawStatus::PENDING);
    }

    public function scopeReceived(Builder $query): void
    {
        $query->where('status', DrawStatus::RECEIVED);
    }

    public function scopeFailed(Builder $query): void
    {
        $query->where('status', DrawStatus::FAILED);
    }

    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', DrawStatus::CANCELLED);
    }

    public function scopeScheduledBefore(Builder $query, string $date): void
    {
        $query->where('scheduled_date', '<=', $date);
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
