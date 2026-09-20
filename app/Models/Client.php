<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientRating;
use App\Enums\DrawStatus;
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

    protected $appends = ['rating'];

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

    /**
     * Derived rating based on the share of draws paid on time in the last
     * 12 months.
     *
     * - none   : no draws in the last 12 months
     * - low    : less than 50% paid on time
     * - medium : 50% to 79% paid on time
     * - high   : 80% or more paid on time
     */
    public function getRatingAttribute(): string
    {
        $statuses = Draw::query()
            ->whereHas('subscription.contract', function ($q) {
                $q->where('client_id', $this->id);
            })
            ->where('due_date', '>=', now()->subMonths(12))
            ->pluck('status');

        if ($statuses->isEmpty()) {
            return ClientRating::NONE->value;
        }

        $onTime = $statuses->filter(fn ($status) => $status === DrawStatus::PAID_ON_TIME->value)->count();
        $total  = $statuses->count();

        $percentage = ($onTime / $total) * 100;

        if ($percentage < 50) {
            return ClientRating::LOW->value;
        }

        if ($percentage < 80) {
            return ClientRating::MEDIUM->value;
        }

        return ClientRating::HIGH->value;
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
