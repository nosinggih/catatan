<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'icon', 'reminder_interval_days'];

    protected static function booted(): void
    {
        static::saving(function (Activity $activity) {
            $activity->normalized_name = self::normalize($activity->name);
            $activity->icon ??= self::guessIcon($activity->name);
        });
    }

    /**
     * Lowercase, trim and collapse whitespace so "Ganti  Sprei " matches "ganti sprei".
     */
    public static function normalize(string $name): string
    {
        return Str::of($name)->lower()->replaceMatches('/[^\pL\pN]+/u', ' ')->squish()->toString();
    }

    public static function guessIcon(string $name): string
    {
        $words = explode(' ', self::normalize($name));

        foreach (config('catatan.icon_keywords') as $keyword => $icon) {
            if (in_array($keyword, $words, true)) {
                return $icon;
            }
        }

        return config('catatan.default_icon');
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * Adds entry statistics as attributes: entries_count, month_count,
     * first_entry_at, last_entry_at and total_cost.
     */
    public function scopeWithStats(Builder $query, string $timezone): Builder
    {
        $monthStart = Carbon::now($timezone)->startOfMonth()->utc();

        return $query
            ->withCount([
                'entries',
                'entries as month_count' => fn ($q) => $q->where('occurred_at', '>=', $monthStart),
            ])
            ->withMin('entries as first_entry_at', 'occurred_at')
            ->withMax('entries as last_entry_at', 'occurred_at')
            ->withSum('entries as total_cost', 'cost');
    }

    /**
     * Average days between entries, or null with fewer than two entries.
     */
    public function averageIntervalDays(): ?float
    {
        if (($this->entries_count ?? 0) < 2) {
            return null;
        }

        $span = Carbon::parse($this->first_entry_at)->diffInSeconds(Carbon::parse($this->last_entry_at));

        return round($span / 86400 / ($this->entries_count - 1), 1);
    }
}
