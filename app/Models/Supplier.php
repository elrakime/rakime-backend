<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\HasUserstamps;

class Supplier extends Model implements HasMedia
{
    use InteractsWithMedia;
    use LogsActivity;
    use HasUserstamps;

    protected $fillable = ['name', 'phone', 'email', 'address', 'is_active', 'wilaya_id', 'metadata'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata'  => 'json',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function wilaya(): BelongsTo
    {
        return $this->belongsTo(Wilaya::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }
}
