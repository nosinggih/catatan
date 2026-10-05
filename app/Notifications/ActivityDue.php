<?php

namespace App\Notifications;

use App\Models\Activity;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ActivityDue extends Notification
{
    public function __construct(public Activity $activity, public int $daysSince) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $a = $this->activity;

        return (new WebPushMessage)
            ->title("{$a->icon} {$a->name}")
            ->body("Sudah {$this->daysSince} hari sejak terakhir. Biasanya tiap {$a->reminder_interval_days} hari.")
            ->icon('/icons/icon-192.png')
            ->badge('/icons/icon-192.png')
            ->tag("activity-{$a->id}")
            ->data(['url' => "/kegiatan/{$a->id}"]);
    }
}
