<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class EntryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->acceptedTerms()->create();
        $this->activity = Activity::factory()->create(['household_id' => $this->user->currentHousehold()->id]);
    }

    public function test_one_tap_records_an_entry_now(): void
    {
        Carbon::setTestNow('2026-10-05 04:00:00');
        $uuid = (string) Str::uuid();

        $this->actingAs($this->user)
            ->postJson('/api/entries', ['uuid' => $uuid, 'activity_id' => $this->activity->id])
            ->assertCreated()
            ->assertJsonPath('data.uuid', $uuid)
            ->assertJsonPath('data.occurred_at', '2026-10-05T04:00:00Z')
            ->assertJsonPath('data.user.id', $this->user->id);
    }

    public function test_sending_the_same_uuid_twice_creates_one_entry(): void
    {
        $payload = ['uuid' => (string) Str::uuid(), 'activity_id' => $this->activity->id];

        $this->actingAs($this->user)->postJson('/api/entries', $payload)->assertCreated();
        $this->actingAs($this->user)->postJson('/api/entries', $payload)->assertOk();

        $this->assertSame(1, Entry::count());
    }

    public function test_rejects_a_future_time_and_another_households_activity(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/entries', [
                'uuid' => (string) Str::uuid(),
                'activity_id' => Activity::factory()->create()->id,
                'occurred_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activity_id', 'occurred_at']);
    }

    public function test_records_details_and_a_past_time(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/entries', [
                'uuid' => (string) Str::uuid(),
                'activity_id' => $this->activity->id,
                'occurred_at' => '2026-10-01T10:00:00+07:00',
                'note' => 'Oli Federal',
                'cost' => 55000,
                'location_name' => 'Bengkel AHASS',
            ])
            ->assertCreated()
            ->assertJsonPath('data.occurred_at', '2026-10-01T03:00:00Z')
            ->assertJsonPath('data.cost', 55000)
            ->assertJsonPath('data.location_name', 'Bengkel AHASS');
    }

    public function test_adds_details_after_the_tap(): void
    {
        $entry = Entry::factory()->create(['activity_id' => $this->activity->id, 'user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->patchJson("/api/entries/{$entry->uuid}", ['note' => 'Sprei biru', 'cost' => null])
            ->assertOk()
            ->assertJsonPath('data.note', 'Sprei biru');

        $this->actingAs($this->user)
            ->patchJson("/api/entries/{$entry->uuid}", ['occurred_at' => null])
            ->assertUnprocessable();
    }

    public function test_undo_deletes_the_entry(): void
    {
        $entry = Entry::factory()->create(['activity_id' => $this->activity->id, 'user_id' => $this->user->id]);

        $this->actingAs($this->user)->deleteJson("/api/entries/{$entry->uuid}")->assertNoContent();

        $this->assertSoftDeleted($entry);
    }

    public function test_lists_history_newest_first(): void
    {
        foreach (['2026-09-01', '2026-10-01', '2026-09-15'] as $date) {
            Entry::factory()->create(['activity_id' => $this->activity->id, 'user_id' => $this->user->id, 'occurred_at' => $date]);
        }

        $this->actingAs($this->user)
            ->getJson("/api/activities/{$this->activity->id}/entries")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.occurred_at', '2026-10-01T00:00:00Z')
            ->assertJsonPath('data.2.occurred_at', '2026-09-01T00:00:00Z');
    }

    public function test_cannot_touch_another_households_entry(): void
    {
        $other = Entry::factory()->create();

        $this->actingAs($this->user)->patchJson("/api/entries/{$other->uuid}", ['note' => 'x'])->assertNotFound();
        $this->actingAs($this->user)->deleteJson("/api/entries/{$other->uuid}")->assertNotFound();
        $this->actingAs($this->user)
            ->postJson('/api/entries', ['uuid' => $other->uuid, 'activity_id' => $this->activity->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['uuid']);
    }
}
