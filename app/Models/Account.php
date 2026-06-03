<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Account extends Model
{
    use LogsActivity;


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
            'min_withdraw_amount' => 'integer',
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
        return $this->hasMany(InstallmentContract::class);
    }
}
