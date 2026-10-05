<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'activity_id' => Activity::factory(),
            'user_id' => User::factory(),
            'occurred_at' => now(),
        ];
    }
}
