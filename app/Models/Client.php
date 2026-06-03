<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Client extends Model
{
    use LogsActivity;


    protected $fillable = [
        'branch_id',
        'wilaya_id',
        'name',
        'phone',
        'birthdate',
        'address',
        'occupation',
        'employer',
        'salary',
        'nin',
        'ccp_number',
        'ccp_key',
        'eccp',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'salary'    => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->dontLogIfAttributesChangedOnly(['phone']);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function installmentContracts(): HasMany
    {
        return $this->hasMany(InstallmentContract::class);
    }
}
