<?php

namespace App\Providers;

use App\Events\OrderStatusChanged;
use App\Listeners\SendOrderStatusNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Les mappings événement => listener pour l'application.
     * 
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrderStatusChanged::class => [
            SendOrderStatusNotification::class,
        ],
    ];

    /**
     * Enregistre les services de l'application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Détermine si les événements et listeners doivent être découverts automatiquement.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
