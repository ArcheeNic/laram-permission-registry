<?php

namespace ArcheeNic\PermissionRegistry\Triggers;

use ArcheeNic\PermissionRegistry\Contracts\PermissionTriggerInterface;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerContext;
use ArcheeNic\PermissionRegistry\ValueObjects\TriggerResult;
use Illuminate\Support\Facades\Log;

/**
 * Пример триггера для добавления доступа в GitLab
 * Это заглушка для демонстрации системы
 */
class ExampleGitlabAccessTrigger implements PermissionTriggerInterface
{
    public function execute(TriggerContext $context): TriggerResult
    {
        try {
            $email = $context->globalFields['email'] ?? null;
            $firstName = $context->globalFields['first_name'] ?? null;
            $lastName = $context->globalFields['last_name'] ?? null;

            // Получить SSH ключ из специфичных полей (не глобальных)
            $sshKeyFieldId = $context->permission->fields()
                ->where('name', 'ssh_key')
                ->value('id');
            
            $sshKey = $context->fieldValues[$sshKeyFieldId] ?? null;

            if (!$email) {
                return TriggerResult::failure('Email обязателен для создания пользователя GitLab');
            }

            if (!$sshKey) {
                return TriggerResult::failure('SSH ключ обязателен для доступа к GitLab');
            }


            // Здесь должна быть реальная логика:
            // 1. Создание пользователя в GitLab
            // 2. Добавление SSH ключа
            // 3. Добавление в нужные проекты/группы

            // Имитируем успешное создание
            $gitlabUserId = 'gitlab_user_' . $context->virtualUserId;

            return TriggerResult::success([
                'gitlab_user_id' => $gitlabUserId,
                'gitlab_username' => strtolower($firstName . '.' . $lastName),
                'gitlab_profile_url' => "https://gitlab.example.com/users/{$gitlabUserId}",
                'ssh_key_added' => true,
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

        if ($log && isset($log->meta['gitlab_user_id'])) {
            $gitlabUserId = $log->meta['gitlab_user_id'];


            // Здесь должна быть реальная логика удаления пользователя из GitLab
        }
    }

    public function getName(): string
    {
        return 'Доступ в GitLab';
    }

    public function getDescription(): string
    {
        return 'Создает пользователя в GitLab и добавляет SSH ключ';
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
                'name' => 'ssh_key',
                'required' => true,
                'description' => 'SSH ключ пользователя',
            ],
        ];
    }
}
