<?php

namespace Artprog\PermissionRegistry;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Artprog\PermissionRegistry\Actions\CheckPermissionFieldsAction;
use Artprog\PermissionRegistry\Actions\GetUserPermissionsAction;
use Artprog\PermissionRegistry\Actions\GrantPermissionAction;
use Artprog\PermissionRegistry\Actions\PermissionChecker;
use Artprog\PermissionRegistry\Actions\RevokePermissionAction;
use Artprog\PermissionRegistry\Actions\SyncUserPermissionsAction;
use Artprog\PermissionRegistry\Facades\PermissionRegistry;
use Artprog\PermissionRegistry\Middleware\CheckPermission;
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
            __DIR__ . '/../config/permission-registry.php', 'permission-registry'
        );
    }

    public function boot(): void
    {
        // Публикация конфигурации
        $this->publishes([
            __DIR__ . '/../config/permission-registry.php' => config_path('permission-registry.php'),
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
        Livewire::component('permission-registry::permissions-list', \Artprog\PermissionRegistry\Livewire\PermissionsList::class);
        Livewire::component('permission-registry::user-permissions', \Artprog\PermissionRegistry\Livewire\UserPermissions::class);
        Livewire::component('permission-registry::users-management', \Artprog\PermissionRegistry\Livewire\UsersManagement::class);
        Livewire::component('permission-registry::fields-list', \Artprog\PermissionRegistry\Livewire\FieldsList::class);
        Livewire::component('permission-registry::groups-list', \Artprog\PermissionRegistry\Livewire\GroupsList::class);
        Livewire::component('permission-registry::positions-list', \Artprog\PermissionRegistry\Livewire\PositionsList::class);
    }
}