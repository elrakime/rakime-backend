<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Sale extends Model
{
    use LogsActivity;


    protected $fillable = [
        'user_id',
        'branch_id',
        'client_id',
        'reference',
        'status',
        'total_amount',
        'paid_amount',
        'note',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'status'       => PurchaseStatus::class,
            'total_amount' => 'integer',
            'paid_amount'  => 'integer',
            'sold_at'      => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function scopeDraft(Builder $query): void
    {
        $query->where('status', PurchaseStatus::DRAFT);
    }

    public function scopePaid(Builder $query): void
    {
        $query->where('status', PurchaseStatus::PAID);
    }

    public function scopePartiallyPaid(Builder $query): void
    {
        $query->where('status', PurchaseStatus::PARTIALLY_PAID);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        return $this->hasMany(InventoryMovement::class, 'moveable_id');
    }
}
