<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin Activity */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'reminder_interval_days' => $this->reminder_interval_days,
            'stats' => $this->when(isset($this->entries_count), fn () => [
                'entries_count' => (int) $this->entries_count,
                'month_count' => (int) $this->month_count,
                'last_entry_at' => $this->last_entry_at ? Carbon::parse($this->last_entry_at)->toIso8601ZuluString() : null,
                'average_interval_days' => $this->averageIntervalDays(),
                'total_cost' => (int) $this->total_cost,
                'next_due_at' => $this->nextDueAt()?->toIso8601ZuluString(),
            ]),
        ];
    }
}
