<?php
/**
 * Скрипт для обновления миграций пакета.
 * Заменяет названия таблиц конфигурационными значениями
 */

$migrationsDir = __DIR__ . '/Migrations';
$files = scandir($migrationsDir);

foreach ($files as $file) {
    if (is_file($migrationsDir . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $content = file_get_contents($migrationsDir . '/' . $file);

        // Замена Schema::create на конфигурационную переменную
        $content = preg_replace(
            '/Schema::create\(\'([a-z_]+)\'/i',
            'Schema::create(config(\'permission-registry.tables.$1\', \'$1\')',
            $content
        );

        // Замена foreignId и constrained на конфигурационные переменные
        $content = preg_replace(
            '/->foreignId\(\'([a-z_]+)_id\'\)->constrained\(\'([a-z_]+)\'\)/i',
            '->foreignId(\'$1_id\')->constrained(config(\'permission-registry.tables.$2\', \'$2\'))',
            $content
        );

        // Обновляем файл
        file_put_contents($migrationsDir . '/' . $file, $content);

        echo "Обновлен файл: $file\n";
    }
}

echo "Все миграции обновлены\n";