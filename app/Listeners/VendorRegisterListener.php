<?php

namespace App\Listeners;

use App\Models\User;
use App\Enums\RoleEnum;
use App\Events\VendorRegisterEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\VendorRegisterNotification;

class VendorRegisterListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(VendorRegisterEvent $event)
    {
        $admin = User::role(RoleEnum::ADMIN)->first();
        if (isset($admin)) {
            $admin->notify(new VendorRegisterNotification($event->store));
        }
    }
}
