<?php

namespace App\Modules\PermissionRegistry\Actions;

use App\Modules\PermissionRegistry\Events\UserPositionChanged;
use App\Modules\PermissionRegistry\Models\UserPosition;
use Illuminate\Support\Facades\Event;

class AssignUserPositionAction
{
    public function handle(int $userId, int $positionId): UserPosition
    {
        // Проверяем, нет ли уже такой должности у пользователя
        $existingPosition = UserPosition::where('user_id', $userId)
            ->where('position_id', $positionId)
            ->first();

        if ($existingPosition) {
            return $existingPosition; // Если должность уже назначена, возвращаем ее
        }

        // Создаем новую связь с должностью
        $userPosition = UserPosition::create([
            'user_id' => $userId,
            'position_id' => $positionId,
        ]);

        Event::dispatch(new UserPositionChanged(
            $userId,
            $positionId,
            null // Не указываем старую должность, так как это добавление новой
        ));

        return $userPosition;
    }

    public function remove(int $userId, int $positionId): bool
    {
        $position = UserPosition::where('user_id', $userId)
            ->where('position_id', $positionId)
            ->first();

        if (!$position) {
            return false;
        }

        $result = $position->delete();

        Event::dispatch(new UserPositionChanged(
            $userId,
            0, // 0 означает удаление должности
            $positionId
        ));

        return $result;
    }
}
