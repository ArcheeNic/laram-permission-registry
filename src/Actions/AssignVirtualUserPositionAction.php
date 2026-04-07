<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Events\VirtualUserPositionChanged;
use ArcheeNic\PermissionRegistry\Models\VirtualUserPosition;
use Illuminate\Support\Facades\Event;

class AssignVirtualUserPositionAction
{
    public function __construct(
        private ReconcileUserPermissionsAction $reconcileUserPermissionsAction
    ) {
    }

    public function handle(int $userId, int $positionId): VirtualUserPosition
    {
        // Проверяем, нет ли уже такой должности у пользователя
        $existingPosition = VirtualUserPosition::where('virtual_user_id', $userId)
            ->where('position_id', $positionId)
            ->first();

        if ($existingPosition) {
            $this->reconcileUserPermissionsAction->handle($userId);
            return $existingPosition; // Если должность уже назначена, возвращаем ее
        }

        // Создаем новую связь с должностью
        $userPosition = VirtualUserPosition::create([
            'virtual_user_id' => $userId,
            'position_id' => $positionId,
        ]);

        Event::dispatch(new VirtualUserPositionChanged(
            $userId,
            $positionId,
            null // Не указываем старую должность, так как это добавление новой
        ));

        $this->reconcileUserPermissionsAction->handle($userId);

        return $userPosition;
    }

    public function remove(int $userId, int $positionId): bool
    {
        $position = VirtualUserPosition::where('virtual_user_id', $userId)
            ->where('position_id', $positionId)
            ->first();

        if (!$position) {
            return false;
        }

        $result = $position->delete();

        Event::dispatch(new VirtualUserPositionChanged(
            $userId,
            0, // 0 означает удаление должности
            $positionId
        ));

        $this->reconcileUserPermissionsAction->handle($userId);

        return $result;
    }
}
