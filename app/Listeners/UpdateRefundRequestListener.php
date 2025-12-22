<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\UpdateRefundRequestEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\UpdateRefundRequestNotification;

class UpdateRefundRequestListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(UpdateRefundRequestEvent $event): void
    {
        $consumer = User::where('id', $event->refund->consumer_id)->first();
        if (isset($consumer)) {
            $consumer->notify(new UpdateRefundRequestNotification($event->refund));
        }
    }
}
