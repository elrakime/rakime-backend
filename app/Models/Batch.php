<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Traits\HasUserstamps;

class Batch extends Model
{
    use HasUserstamps;
    protected $fillable = [
        'stock_id',
        'source_id',
        'source_type',
        'purchase_price',
        'initial_quantity',
        'current_quantity',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price'   => 'decimal:2',
            'initial_quantity' => 'integer',
            'current_quantity' => 'integer',
        ];
    }

    public function scopeByUserBranches(Builder $query): void
    {
        $user = auth()->user();

        if (! $user || $user->hasRole(Role::ADMIN->value)) {
            return;
        }

        $branchIds = $user->branches()->pluck('branch_id');

        if ($branchIds->isNotEmpty()) {
            $query->whereHas('stock.inventory', fn (Builder $q) => $q->whereIn('branch_id', $branchIds));
        }
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BatchAllocation::class);
    }
}
