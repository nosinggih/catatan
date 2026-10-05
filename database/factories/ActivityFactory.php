<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'name' => ucfirst(fake()->unique()->words(2, true)),
        ];
    }
}
