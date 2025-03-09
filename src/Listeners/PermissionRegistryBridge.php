<?php

namespace App\Listeners;

use App\Modules\PermissionRegistry\Events\AfterPermissionGranted;
use App\Modules\PermissionRegistry\Events\AfterPermissionRevoked;
use App\Modules\PermissionRegistry\Events\UserPositionChanged;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;

class PermissionRegistryBridge
{
    /**
     * Обработка события выдачи доступа
     */
    public function handlePermissionGranted(AfterPermissionGranted $event): void
    {
        // Здесь мы можем диспетчеризовать событие в другие модули
        // Например, если нужно уведомить другой модуль о выдаче доступа
        // Event::dispatch(new \App\Modules\OtherModule\Events\AccessGranted(
        //     $event->userId,
        //     $event->permissionName,
        //     $event->service
        // ));
    }

    /**
     * Обработка события отзыва доступа
     */
    public function handlePermissionRevoked(AfterPermissionRevoked $event): void
    {
        // Аналогично для события отзыва доступа
    }

    /**
     * Обработка события изменения должности пользователя
     */
    public function handlePositionChanged(UserPositionChanged $event): void
    {
        // Аналогично для события изменения должности
    }

    /**
     * Регистрация слушателей для события.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            AfterPermissionGranted::class => 'handlePermissionGranted',
            AfterPermissionRevoked::class => 'handlePermissionRevoked',
            UserPositionChanged::class => 'handlePositionChanged',
        ];
    }
}
