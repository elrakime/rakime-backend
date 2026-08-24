<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasStatusHistory;
use App\Traits\HasStatusGuard;
use App\Traits\HasUserstamps;

class Subscription extends Model
{
    use HasStatusHistory;
    use HasStatusGuard;
    use HasUserstamps;

    protected $fillable = [
        'contract_id',
        'reference',
        'subscription_number',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status'              => SubscriptionStatus::class,
            'subscription_number' => 'integer',
            'amount'              => 'decimal:2',
            'created_at'          => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', SubscriptionStatus::ACTIVE);
    }

    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', SubscriptionStatus::CANCELLED);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', SubscriptionStatus::COMPLETED);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function draws(): HasMany
    {
        return $this->hasMany(Draw::class, 'subscription_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference = '';
        });

        static::created(function (self $model) {
            $contractReference = $model->contract?->reference ?? '';
            $subscriptionNumber = str_pad((string) $model->subscription_number, 2, '0', STR_PAD_LEFT);

            $model->updateQuietly([
                'reference' => $contractReference . '-' . $subscriptionNumber,
            ]);
        });
    }
}
