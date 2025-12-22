<?php

namespace App\Listeners;

use App\Events\UpdateOrderStatusEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\UpdateOrderStatusNotification;

class UpdateOrderStatusListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(UpdateOrderStatusEvent $event)
    {
        $consumer = $event->order->consumer;
        if (isset($consumer)) {
            $consumer->notify(new UpdateOrderStatusNotification($event->order));
        }
    }
}
