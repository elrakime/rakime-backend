<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PriceType;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUserstamps;

class Price extends Model
{
    use HasUserstamps;
    protected $fillable = [
        'stock_id',
        'type',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'type'   => PriceType::class,
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
}
