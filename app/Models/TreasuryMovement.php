<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TreasuryMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TreasuryMovement extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'treasury_id',
        'movement_type',
        'amount',
        'reference_type',
        'reference_id',
        'note',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => TreasuryMovementType::class,
            'amount'        => 'decimal:2',
            'created_at'         => 'datetime',
        ];
    }

    public function scopeOfType(Builder $query, TreasuryMovementType $type): void
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

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}
