<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Branch extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'code'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
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

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'branch_accounts');
    }
}
