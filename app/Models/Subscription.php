<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUserstamps;

class Subscription extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'contract_id',
        'reference',
        'subscription_number',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'subscription_number' => 'integer',
            'amount'              => 'decimal:2',
            'created_at'          => 'datetime',
        ];
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
