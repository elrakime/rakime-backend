<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasStatusHistory;
use App\Traits\HasUserstamps;
use App\Enums\SaleReturnStatus;

class SaleReturn extends Model
{
    use HasStatusHistory;
    use HasUserstamps;

    protected $fillable = [
        'sale_id',
        'reference',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => SaleReturnStatus::class,
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
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
            $model->updateQuietly(['reference' => 'SRET-' . now()->format('Y') . '-' . str_pad((string) $model->id, 4, '0', STR_PAD_LEFT)]);
        });
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', SaleReturnStatus::COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', SaleReturnStatus::PENDING);
    }
}
