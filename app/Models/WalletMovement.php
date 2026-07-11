<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WalletMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Traits\HasUserstamps;

class WalletMovement extends Model
{
    use HasUserstamps;
    

    protected $fillable = [
        'wallet_id',
        'movement_type',
        'amount',
        'old_balance',
        'new_balance',
        'source_type',
        'source_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => WalletMovementType::class,
            'amount'        => 'decimal:2',
            'old_balance'   => 'decimal:2',
            'new_balance'   => 'decimal:2',
            'created_at'    => 'datetime',
        ];
    }

    public function scopeOfType(Builder $query, WalletMovementType $type): void
    {
        $query->where('movement_type', $type);
    }

    public function scopeInflow(Builder $query): void
    {
        $query->where('amount', '>', 0);
    }

    public function scopeOutflow(Builder $query): void
    {
        $query->where('amount', '<', 0);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }
}
