<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasUserstamps;

class Account extends Model
{
    use LogsActivity;
    use HasUserstamps;


    protected $fillable = [
        'name',
        'ccp_number',
        'ccp_key',
        'draw_day',
        'min_withdraw_amount',
        'max_withdraw_count',
    ];

    protected function casts(): array
    {
        return [
            'draw_day'            => 'integer',
            'min_withdraw_amount' => 'decimal:2',
            'max_withdraw_count'  => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_accounts');
    }

    public function installmentContracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function drawLocks(): HasMany
    {
        return $this->hasMany(AccountDrawLock::class);
    }
}
