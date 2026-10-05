<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->acceptedTerms()->create();
    }

    private function activity(array $attributes = []): Activity
    {
        return Activity::factory()->create([
            'household_id' => $this->user->currentHousehold()->id,
            ...$attributes,
        ]);
    }

    public function test_requires_accepted_terms(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/activities')->assertForbidden();
    }

    public function test_creates_an_activity_with_a_guessed_icon(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/activities', ['name' => 'Ganti Sprei'])
            ->assertOk()
            ->assertJsonPath('created', true)
            ->assertJsonPath('data.name', 'Ganti Sprei')
            ->assertJsonPath('data.icon', '🛏️')
            ->assertJsonPath('data.stats.entries_count', 0)
            ->assertJsonPath('data.stats.last_entry_at', null);

        $this->assertSame('ganti sprei', Activity::first()->normalized_name);
    }

    public function test_returns_the_existing_activity_for_a_duplicate_name(): void
    {
        $existing = $this->activity(['name' => 'Ganti sprei']);

        $this->actingAs($this->user)
            ->postJson('/api/activities', ['name' => '  ganti   SPREI '])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('data.id', $existing->id);

        $this->assertSame(1, Activity::count());
    }

    public function test_lists_only_the_users_household_activities_most_used_first(): void
    {
        $rare = $this->activity(['name' => 'Servis AC']);
        $often = $this->activity(['name' => 'Isi galon']);
        Entry::factory()->count(3)->create(['activity_id' => $often->id, 'user_id' => $this->user->id]);
        Entry::factory()->create(['activity_id' => $rare->id, 'user_id' => $this->user->id]);
        Activity::factory()->create(['name' => 'Milik orang lain']);

        $this->actingAs($this->user)
            ->getJson('/api/activities')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Isi galon')
            ->assertJsonPath('data.1.name', 'Servis AC');
    }

    public function test_stats_count_this_month_in_the_viewers_timezone(): void
    {
        // 1 Oct 2026 08:00 WIB; 30 Sep 2026 23:00 UTC is already 1 Oct in Jakarta.
        Carbon::setTestNow('2026-10-10 01:00:00');
        $activity = $this->activity(['name' => 'Ganti sprei']);
        foreach (['2026-09-20 03:00:00', '2026-09-30 23:00:00', '2026-10-09 03:00:00'] as $at) {
            Entry::factory()->create(['activity_id' => $activity->id, 'user_id' => $this->user->id, 'occurred_at' => $at, 'cost' => 5000]);
        }

        $this->actingAs($this->user)
            ->getJson("/api/activities/{$activity->id}?tz=Asia/Jakarta")
            ->assertOk()
            ->assertJsonPath('data.stats.entries_count', 3)
            ->assertJsonPath('data.stats.month_count', 2)
            ->assertJsonPath('data.stats.last_entry_at', '2026-10-09T03:00:00Z')
            ->assertJsonPath('data.stats.average_interval_days', 9.5)
            ->assertJsonPath('data.stats.total_cost', 15000);

        $this->actingAs($this->user)
            ->getJson("/api/activities/{$activity->id}?tz=UTC")
            ->assertJsonPath('data.stats.month_count', 1);
    }

    public function test_renames_an_activity_but_rejects_a_duplicate_name(): void
    {
        $activity = $this->activity(['name' => 'Cuci motor']);
        $this->activity(['name' => 'Isi galon']);

        $this->actingAs($this->user)
            ->patchJson("/api/activities/{$activity->id}", ['name' => 'isi galon'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'Kegiatan dengan nama ini sudah ada.']);

        $this->actingAs($this->user)
            ->patchJson("/api/activities/{$activity->id}", ['name' => 'Cuci motor Beat'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Cuci motor Beat');
    }

    public function test_deletes_an_activity(): void
    {
        $activity = $this->activity();

        $this->actingAs($this->user)->deleteJson("/api/activities/{$activity->id}")->assertNoContent();

        $this->assertSoftDeleted($activity);
        $this->actingAs($this->user)->getJson('/api/activities')->assertJsonCount(0, 'data');
    }

    public function test_cannot_touch_another_households_activity(): void
    {
        $other = Activity::factory()->create();

        $this->actingAs($this->user)->getJson("/api/activities/{$other->id}")->assertNotFound();
        $this->actingAs($this->user)->patchJson("/api/activities/{$other->id}", ['name' => 'x'])->assertNotFound();
        $this->actingAs($this->user)->deleteJson("/api/activities/{$other->id}")->assertNotFound();
        $this->actingAs($this->user)->getJson("/api/activities/{$other->id}/entries")->assertNotFound();
    }

    public function test_returns_templates(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/templates')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Ganti sprei');
    }
}
