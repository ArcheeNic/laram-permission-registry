<?php

namespace ArcheeNic\PermissionRegistry\Triggers;

use ArcheeNic\PermissionRegistry\Contracts\PermissionTriggerInterface;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerContext;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerResult;
use Illuminate\Support\Facades\Log;

/**
 * Пример триггера для создания пользователя в Bitrix24
 * Это заглушка для демонстрации системы
 */
class ExampleBitrix24CreateUserTrigger implements PermissionTriggerInterface
{
    public function execute(TriggerContext $context): TriggerResult
    {
        try {
            $email = $context->globalFields['email'] ?? null;
            $firstName = $context->globalFields['first_name'] ?? null;
            $lastName = $context->globalFields['last_name'] ?? null;
            $phone = $context->globalFields['phone'] ?? null;

            if (!$email) {
                return TriggerResult::failure('Email обязателен для создания пользователя B24');
            }


            // Здесь должна быть реальная логика создания пользователя в B24
            // Например, вызов REST API Bitrix24

            // Имитируем успешное создание
            $b24UserId = 'b24_user_' . $context->virtualUserId;

            return TriggerResult::success([
                'b24_user_id' => $b24UserId,
                'b24_profile_url' => "https://example.bitrix24.ru/company/personal/user/{$b24UserId}/",
                'created_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return TriggerResult::failure($e->getMessage());
        }
    }

    public function canRollback(): bool
    {
        return true;
    }

    public function rollback(TriggerContext $context): void
    {
        // Получить ID созданного пользователя из логов
        $log = $context->grantedPermission->executionLogs()
            ->where('permission_trigger_id', function ($query) {
                $query->select('id')
                    ->from('permission_triggers')
                    ->where('class_name', self::class)
                    ->limit(1);
            })
            ->where('status', 'success')
            ->first();

        if ($log && isset($log->meta['b24_user_id'])) {
            $b24UserId = $log->meta['b24_user_id'];

            // Здесь должна быть реальная логика удаления пользователя из B24
        }
    }

    public function getName(): string
    {
        return 'Создание пользователя в Bitrix24';
    }

    public function getDescription(): string
    {
        return 'Создает учетную запись пользователя в системе Bitrix24';
    }

    public function getRequiredFields(): array
    {
        return [
            [
                'name' => 'email',
                'required' => true,
                'description' => 'Email адрес пользователя',
            ],
            [
                'name' => 'first_name',
                'required' => false,
                'description' => 'Имя пользователя',
            ],
            [
                'name' => 'last_name',
                'required' => false,
                'description' => 'Фамилия пользователя',
            ],
            [
                'name' => 'phone',
                'required' => false,
                'description' => 'Номер телефона',
            ],
        ];
    }
}
