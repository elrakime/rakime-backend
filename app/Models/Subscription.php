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
        'total_months',
        'status',
        'draw_date',
    ];

    protected function casts(): array
    {
        return [
            'status'              => SubscriptionStatus::class,
            'subscription_number' => 'integer',
            'amount'              => 'integer',
            'total_months'        => 'integer',
            'draw_date'           => 'date',
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
}
