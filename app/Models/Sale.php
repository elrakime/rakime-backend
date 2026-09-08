<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasUserstamps;

class Sale extends Model
{
    use LogsActivity;
    use HasUserstamps;

    protected $fillable = [
        'branch_id',
        'client_id',
        'reference',
        'gross_amount',
        'tax_rate',
        'tax_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'total_amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount'    => 'decimal:2',
            'tax_rate'        => 'decimal:2',
            'tax_amount'      => 'decimal:2',
            'discount_value'  => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount'    => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function scopeByUserBranches(Builder $query): void
    {
        $user = auth()->user();

        if (! $user || $user->hasRole(Role::ADMIN->value)) {
            return;
        }

        $branchIds = $user->branches()->pluck('branch_id');

        if ($branchIds->isNotEmpty()) {
            $query->whereIn('branch_id', $branchIds);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'source_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference = '';
        });

        static::created(function (self $model) {
            $model->updateQuietly(['reference' => 'SAL-' . now()->format('Y') . '-' . str_pad((string) $model->id, 4, '0', STR_PAD_LEFT)]);
        });
    }
}
