<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Notifications\ActivityDue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendReminders extends Command
{
    protected $signature = 'catatan:send-reminders {--force : Ignore quiet hours}';

    protected $description = 'Push a reminder for every activity that is due again';

    public function handle(): int
    {
        $hour = now(config('catatan.reminder_timezone'))->hour;
        [$from, $until] = config('catatan.reminder_hours');

        if (! $this->option('force') && ($hour < $from || $hour >= $until)) {
            $this->info('Quiet hours, no reminders sent.');

            return self::SUCCESS;
        }

        $sent = 0;

        Activity::whereNotNull('reminder_interval_days')
            ->withMax('entries as last_entry_at', 'occurred_at')
            // Only members who turned on notifications on at least one device.
            ->with(['household.members' => fn ($q) => $q->has('pushSubscriptions')])
            ->lazyById()
            ->each(function (Activity $activity) use (&$sent) {
                $due = $activity->nextDueAt();

                // Remind once per due date: logging the activity moves the due date
                // forward, which makes it eligible again next time.
                if (! $due || $due->isFuture() || ($activity->reminded_at && $activity->reminded_at->gte($due))) {
                    return;
                }

                $members = $activity->household->members;
                if ($members->isEmpty()) {
                    return;
                }

                $days = (int) floor(Carbon::parse($activity->last_entry_at)->diffInDays(now()));
                try {
                    Notification::send($members, new ActivityDue($activity, $days));
                } catch (Throwable $e) {
                    // One broken push service must not stop the other reminders.
                    report($e);

                    return;
                }
                $activity->forceFill(['reminded_at' => now()])->saveQuietly();
                $sent++;
            });

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
