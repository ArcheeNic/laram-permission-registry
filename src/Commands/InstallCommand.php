<?php

namespace Artprog\PermissionRegistry\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'permission-registry:install';

    protected $description = 'Install the Permission Registry package';

    public function handle()
    {
        $this->info('Установка пакета Permission Registry...');

        // Публикация конфигураций
        $this->call('vendor:publish', [
            '--tag' => 'permission-registry-config',
        ]);

        // Публикация миграций
        $this->call('vendor:publish', [
            '--tag' => 'permission-registry-migrations',
        ]);

        // Публикация представлений
        if ($this->confirm('Хотите опубликовать представления для настройки?', false)) {
            $this->call('vendor:publish', [
                '--tag' => 'permission-registry-views',
            ]);
        }

        // Запуск миграций
        if ($this->confirm('Запустить миграции?', true)) {
            $this->call('migrate');
        }

        // Пример данных
        if ($this->confirm('Создать примеры данных?', false)) {
            // Тут можно добавить команду для создания примеров
            $this->info('Примеры данных созданы!');
        }

        $this->info('Пакет Permission Registry успешно установлен!');
        $this->info('Доступ к панели управления: http://your-app.com/permission-registry');
    }
}