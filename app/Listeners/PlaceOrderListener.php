<?php

namespace App\Listeners;

use App\Models\User;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use App\Events\PlaceOrderEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\PlaceOrderNotification;

class PlaceOrderListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PlaceOrderEvent $event)
    {
        $consumer = $event->order->consumer;
        if (isset($consumer) && is_null($event->order->parent_id)) {
            $consumer->notify(new PlaceOrderNotification($event->order, RoleEnum::CONSUMER));
        }

        foreach ($event->order->sub_orders as $sub_order) {
            if (isset($sub_order->store_id)) {
                $vendor = Helpers::getStoreById($sub_order->store_id)?->vendor;
                $vendor->notify(new PlaceOrderNotification($sub_order, RoleEnum::VENDOR));
            }
        }

        $admin = User::role(RoleEnum::ADMIN)->first();
        if (isset($admin)) {
            $admin->notify(new PlaceOrderNotification($event->order, RoleEnum::ADMIN));
        }
    }
}
