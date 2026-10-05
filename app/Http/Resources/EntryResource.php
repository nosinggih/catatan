<?php

namespace App\Http\Resources;

use App\Models\Entry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Entry */
class EntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'activity_id' => $this->activity_id,
            'occurred_at' => $this->occurred_at->toIso8601ZuluString(),
            'note' => $this->note,
            'cost' => $this->cost,
            'location_name' => $this->location_name,
            'photo_url' => $this->photo_path ? "/api/entries/{$this->uuid}/photo?v={$this->updated_at->timestamp}" : null,
            'user' => $this->whenLoaded('user', fn () => ['id' => $this->user->id, 'name' => $this->user->name]),
        ];
    }
}
