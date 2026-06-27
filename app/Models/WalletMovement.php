<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WalletMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletMovement extends Model
{
    

    protected $fillable = [
        'wallet_id',
        'movement_type',
        'amount',
        'source_type',
        'source_id',
        'note',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => WalletMovementType::class,
            'amount'        => 'decimal:2',
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

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }
}
