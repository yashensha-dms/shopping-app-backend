<?php

namespace App\Listeners;

use App\Events\SignUpBonusPointsEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\SignUpBonusPointsNotification;

class SignUpBonusPointsListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(SignUpBonusPointsEvent $event): void
    {
        $user = $event->user;
        if (isset($user)) {
            $user->notify(new SignUpBonusPointsNotification($user));
        }
    }
}
