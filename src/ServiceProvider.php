<?php

namespace ArcheeNic\PermissionRegistry;

use ArcheeNic\PermissionRegistry\Livewire\FieldsList;
use ArcheeNic\PermissionRegistry\Livewire\GroupsList;
use ArcheeNic\PermissionRegistry\Livewire\PermissionsList;
use ArcheeNic\PermissionRegistry\Livewire\PositionsList;
use ArcheeNic\PermissionRegistry\Livewire\UserPermissions;
use ArcheeNic\PermissionRegistry\Livewire\UsersManagement;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use ArcheeNic\PermissionRegistry\Actions\CheckPermissionFieldsAction;
use ArcheeNic\PermissionRegistry\Actions\GetUserPermissionsAction;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Actions\PermissionChecker;
use ArcheeNic\PermissionRegistry\Actions\RevokePermissionAction;
use ArcheeNic\PermissionRegistry\Actions\SyncUserPermissionsAction;
use ArcheeNic\PermissionRegistry\Facades\PermissionRegistry;
use ArcheeNic\PermissionRegistry\Middleware\CheckPermission;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        // Регистрация менеджера в контейнере
        $this->app->singleton('permission-registry', function ($app) {
            return new PermissionRegistryManager(
                $app->make(PermissionChecker::class),
                $app->make(GrantPermissionAction::class),
                $app->make(RevokePermissionAction::class),
                $app->make(GetUserPermissionsAction::class),
                $app->make(CheckPermissionFieldsAction::class),
                $app->make(SyncUserPermissionsAction::class)
            );
        });

        // Регистрация алиаса фасада
        $this->app->alias('permission-registry', PermissionRegistry::class);

        // Регистрация конфигурации
        $this->mergeConfigFrom(
            __DIR__ . '/config/permission-registry.php',
            'permission-registry'
        );
    }

    public function boot(): void
    {
        // Публикация конфигурации
        $this->publishes([
            __DIR__ . '/config/permission-registry.php' => config_path('permission-registry.php'),
        ], 'permission-registry-config');

        // Публикация миграций
        $this->publishes([
            __DIR__ . '/Database/Migrations/' => database_path('migrations'),
        ], 'permission-registry-migrations');

        // Загрузка миграций
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        // Загрузка маршрутов
        $this->loadRoutes();

        // Загрузка представлений
        $this->loadViewsFrom(__DIR__ . '/Views', 'permission-registry');

        // Публикация представлений
        $this->publishes([
            __DIR__ . '/Views' => resource_path('views/vendor/permission-registry'),
        ], 'permission-registry-views');

        // Загрузка языковых файлов
        $this->loadTranslationsFrom(__DIR__ . '/Lang', 'permission-registry');

        // Публикация языковых файлов
        $this->publishes([
            __DIR__ . '/Lang' => resource_path('lang/vendor/permission-registry'),
        ], 'permission-registry-lang');

        // Регистрация middleware
        Route::aliasMiddleware('permission', CheckPermission::class);

        // Регистрация Livewire компонентов
        if (class_exists(Livewire::class)) {
            $this->registerLivewireComponents();
        }
    }

    protected function loadRoutes(): void
    {
        Route::prefix('permission-registry')
            ->name('permission-registry::')
            ->middleware(config('permission-registry.middlewares', ['web', 'auth']))
            ->group(__DIR__ . '/Routes/web.php');
    }

    protected function registerLivewireComponents(): void
    {
        // Регистрация Livewire компонентов
        Livewire::component('permission-registry::permissions-list', PermissionsList::class);
        Livewire::component('permission-registry::user-permissions', UserPermissions::class);
        Livewire::component('permission-registry::users-management', UsersManagement::class);
        Livewire::component('permission-registry::fields-list', FieldsList::class);
        Livewire::component('permission-registry::groups-list', GroupsList::class);
        Livewire::component('permission-registry::positions-list', PositionsList::class);
    }
}