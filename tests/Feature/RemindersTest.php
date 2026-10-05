<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Entry;
use App\Models\User;
use App\Notifications\ActivityDue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use Tests\TestCase;

class RemindersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        // 10:00 in Jakarta, inside reminder hours.
        Carbon::setTestNow('2026-10-10 03:00:00');
        $this->user = User::factory()->acceptedTerms()->create();
        $this->activity = Activity::factory()->create([
            'household_id' => $this->user->currentHousehold()->id,
            'name' => 'Ganti sprei',
            'reminder_interval_days' => 7,
        ]);
        $this->user->updatePushSubscription('https://push.example.com/abc', 'key', 'auth', 'aes128gcm');
    }

    private function logAt(string $at): void
    {
        Entry::factory()->create(['activity_id' => $this->activity->id, 'user_id' => $this->user->id, 'occurred_at' => $at]);
    }

    public function test_reminds_household_members_once_when_an_activity_is_due(): void
    {
        $this->logAt('2026-10-01 03:00:00');

        $this->artisan('catatan:send-reminders')->assertSuccessful();
        $this->artisan('catatan:send-reminders')->assertSuccessful();

        Notification::assertSentToTimes($this->user, ActivityDue::class, 1);
        Notification::assertSentTo($this->user, ActivityDue::class, function (ActivityDue $n) {
            $message = $n->toWebPush($this->user, $n)->toArray();

            return $n->daysSince === 9
                && $message['title'] === '🛏️ Ganti sprei'
                && $message['data']['url'] === "/kegiatan/{$this->activity->id}";
        });
    }

    public function test_reminds_again_after_the_activity_is_logged_and_due_again(): void
    {
        $this->logAt('2026-10-01 03:00:00');
        $this->artisan('catatan:send-reminders');

        Carbon::setTestNow('2026-10-10 04:00:00');
        $this->logAt('2026-10-10 04:00:00');
        $this->artisan('catatan:send-reminders');

        Carbon::setTestNow('2026-10-17 05:00:00');
        $this->artisan('catatan:send-reminders');

        Notification::assertSentToTimes($this->user, ActivityDue::class, 2);
    }

    public function test_does_not_remind_before_due_without_entries_or_without_interval(): void
    {
        $this->logAt('2026-10-05 03:00:00');
        Activity::factory()->create(['household_id' => $this->user->currentHousehold()->id, 'reminder_interval_days' => 3]);
        $noInterval = Activity::factory()->create(['household_id' => $this->user->currentHousehold()->id]);
        Entry::factory()->create(['activity_id' => $noInterval->id, 'user_id' => $this->user->id, 'occurred_at' => '2026-01-01']);

        $this->artisan('catatan:send-reminders');

        Notification::assertNothingSent();
    }

    public function test_skips_members_without_a_notification_device_until_they_add_one(): void
    {
        $this->user->pushSubscriptions()->delete();
        $this->logAt('2026-10-01 03:00:00');

        $this->artisan('catatan:send-reminders')->assertSuccessful();
        Notification::assertNothingSent();
        $this->assertNull($this->activity->fresh()->reminded_at);

        $this->user->updatePushSubscription('https://push.example.com/abc', 'key', 'auth', 'aes128gcm');
        $this->artisan('catatan:send-reminders')->assertSuccessful();
        Notification::assertSentToTimes($this->user, ActivityDue::class, 1);
    }

    public function test_stays_quiet_at_night(): void
    {
        $this->logAt('2026-10-01 03:00:00');
        Carbon::setTestNow('2026-10-10 16:00:00'); // 23:00 in Jakarta

        $this->artisan('catatan:send-reminders')->expectsOutput('Quiet hours, no reminders sent.');

        Notification::assertNothingSent();
    }

    public function test_activity_api_exposes_the_interval_and_next_due_date(): void
    {
        $this->logAt('2026-10-01 03:00:00');

        $this->actingAs($this->user)
            ->patchJson("/api/activities/{$this->activity->id}", ['reminder_interval_days' => 14])
            ->assertOk()
            ->assertJsonPath('data.reminder_interval_days', 14)
            ->assertJsonPath('data.stats.next_due_at', '2026-10-15T03:00:00Z');

        $this->actingAs($this->user)
            ->patchJson("/api/activities/{$this->activity->id}", ['reminder_interval_days' => 0])
            ->assertUnprocessable();

        $this->actingAs($this->user)
            ->patchJson("/api/activities/{$this->activity->id}", ['reminder_interval_days' => null])
            ->assertOk()
            ->assertJsonPath('data.stats.next_due_at', null);
    }

    public function test_the_reminder_is_scheduled_hourly(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('catatan:send-reminders');
        $this->assertInstanceOf(WebPushMessage::class, (new ActivityDue($this->activity, 1))->toWebPush($this->user, new ActivityDue($this->activity, 1)));
    }
}
