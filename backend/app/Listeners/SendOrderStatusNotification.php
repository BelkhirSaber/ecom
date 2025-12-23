<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged as OrderStatusChangedEvent;
use App\Mail\OrderStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusNotification implements ShouldQueue
{
    /**
     * Gère l'événement de changement de statut de commande.
     * Envoie un email de notification au client.
     * 
     * @param OrderStatusChangedEvent $event L'événement déclenché
     * @return void
     */
    public function handle(OrderStatusChangedEvent $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if (!$user || !$user->email) {
            Log::channel('catalogue')->warning('order.notification_skipped', [
                'order_id' => $order->id,
                'reason' => 'No user email found',
            ]);
            return;
        }

        try {
            Mail::to($user->email)->send(
                new OrderStatusChanged($order, $event->oldStatus, $event->newStatus)
            );

            Log::channel('catalogue')->info('order.notification_sent', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
            ]);
        } catch (\Throwable $e) {
            Log::channel('catalogue')->error('order.notification_failed', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
