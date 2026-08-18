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
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\HasUserstamps;

class Client extends Model implements HasMedia
{
    use InteractsWithMedia;
    use LogsActivity;
    use HasUserstamps;

    protected $fillable = [
        'branch_id',
        'wilaya_id',
        'firstname',
        'lastname',
        'phone',
        'birthdate',
        'address',
        'occupation',
        'employer',
        'nin',
        'ccp_number',
        'ccp_key',
        'eccp',
        'is_banned',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'metadata'  => 'json',
            'is_banned' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->dontLogIfAttributesChangedOnly(['phone']);
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
        return $this->hasMany(Contract::class);
    }

    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }
}
