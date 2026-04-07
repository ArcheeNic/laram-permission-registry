<?php

namespace ArcheeNic\PermissionRegistry\Actions;

use ArcheeNic\PermissionRegistry\Models\VirtualUserFieldValue;

/**
 * Генерация display name для виртуального пользователя по шаблону из конфига
 */
class GenerateDisplayNameAction
{
    /**
     * Сгенерировать display name для пользователя
     *
     * @param int $virtualUserId ID виртуального пользователя
     * @return string
     */
    public function execute(int $virtualUserId): string
    {
        $template = config('permission-registry.display_name_template', '{1} {2}');
        
        $fields = VirtualUserFieldValue::where(VirtualUserFieldValue::VIRTUAL_USER_ID, $virtualUserId)
            ->with('field')
            ->get();
        
        foreach ($fields as $fieldValue) {
            $fieldId = $fieldValue->permission_field_id;
            $template = str_replace("{{$fieldId}}", $fieldValue->value ?? '', $template);
        }
        
        // Удалить оставшиеся плейсхолдеры (если поле не заполнено)
        $template = preg_replace('/\{\d+\}/', '', $template);
        
        // Убрать лишние пробелы
        $template = trim(preg_replace('/\s+/', ' ', $template));
        
        return $template ?: 'User #' . $virtualUserId;
    }
}
