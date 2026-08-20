<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\HasUserstamps;

class Branch extends Model implements HasMedia
{
    use InteractsWithMedia;
    use LogsActivity;
    use HasUserstamps;

    protected $fillable = [
        'name',
        'code',
        'shop_name',
        'address',
        'phone',
        'wilaya_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
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
            $query->whereIn('id', $branchIds);
        }
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branches');
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branches')
            ->role(\App\Enums\Role::MANAGER->value);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'owner_id')->where('owner_type', 'branch');
    }

    public function restockOrders(): HasMany
    {
        return $this->hasMany(Restock::class);
    }

    public function installmentContracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class);
    }

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'branch_accounts');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }
}
