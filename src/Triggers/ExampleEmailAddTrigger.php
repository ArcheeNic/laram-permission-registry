<?php

namespace ArcheeNic\PermissionRegistry\Triggers;

use ArcheeNic\PermissionRegistry\Contracts\PermissionTriggerInterface;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerContext;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerResult;
use Illuminate\Support\Facades\Log;

/**
 * Пример триггера для добавления корпоративной почты
 * Это заглушка для демонстрации системы
 */
class ExampleEmailAddTrigger implements PermissionTriggerInterface
{
    public function execute(TriggerContext $context): TriggerResult
    {
        try {
            $email = $context->globalFields['email'] ?? null;
            $firstName = $context->globalFields['first_name'] ?? null;
            $lastName = $context->globalFields['last_name'] ?? null;

            if (!$email) {
                return TriggerResult::failure('Email не заполнен');
            }


            // Здесь должна быть реальная логика создания почтового ящика
            // Например, вызов API почтового сервера

            // Имитируем успешное создание
            return TriggerResult::success([
                'email_account_id' => 'example_' . $context->virtualUserId,
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
        // Получить ID созданного аккаунта из логов
        $log = $context->grantedPermission->executionLogs()
            ->where('permission_trigger_id', function ($query) {
                $query->select('id')
                    ->from('permission_triggers')
                    ->where('class_name', self::class)
                    ->limit(1);
            })
            ->where('status', 'success')
            ->first();

        if ($log && isset($log->meta['email_account_id'])) {
            $accountId = $log->meta['email_account_id'];


            // Здесь должна быть реальная логика удаления почтового ящика
        }
    }

    public function getName(): string
    {
        return 'Создание корпоративной почты';
    }

    public function getDescription(): string
    {
        return 'Создает корпоративный почтовый ящик для пользователя';
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
        ];
    }
}
