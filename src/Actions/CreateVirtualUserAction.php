<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Events\UserCreated;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use Illuminate\Support\Facades\Event;

class CreateVirtualUserAction
{
    public function __construct(
        private UpdateVirtualUserGlobalFieldsAction $updateGlobalFieldsAction
    ) {
    }

    /**
     * Создать виртуального пользователя
     *
     * @param array $globalFields Массив ['field_id' => 'value']
     * @return VirtualUser
     */
    public function handle(array $globalFields = []): VirtualUser
    {
        // Создаем пользователя с временным именем
        $user = VirtualUser::create([
            'name' => 'User #' . uniqid(),
        ]);

        // Сохраняем значения глобальных полей и генерируем display name
        if (!empty($globalFields)) {
            $this->updateGlobalFieldsAction->execute($user->id, $globalFields);
            
            // Перезагружаем пользователя, чтобы получить обновленное имя
            $user->refresh();
        }

        // Получаем email из глобальных полей для события
        $email = '';
        if (!empty($globalFields)) {
            $emailField = $user->fieldValues()->whereHas('field', function ($query) {
                $query->where('name', 'like', '%email%');
            })->first();
            
            if ($emailField) {
                $email = $emailField->value;
            }
        }

        // Диспетчеризация события создания пользователя
        Event::dispatch(new UserCreated($user->id, $email));

        return $user;
    }
}
