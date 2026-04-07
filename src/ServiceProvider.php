<?php

namespace ArcheeNic\PermissionRegistry;

use ArcheeNic\PermissionRegistry\Console\ExpireApprovalRequestsCommand;
use ArcheeNic\PermissionRegistry\Livewire\ApprovalPolicyManager;
use ArcheeNic\PermissionRegistry\Livewire\AttestationsList;
use ArcheeNic\PermissionRegistry\Livewire\FieldsList;
use ArcheeNic\PermissionRegistry\Livewire\GroupsList;
use ArcheeNic\PermissionRegistry\Livewire\ManualTasksList;
use ArcheeNic\PermissionRegistry\Livewire\MyPermissions;
use ArcheeNic\PermissionRegistry\Livewire\MyRequests;
use ArcheeNic\PermissionRegistry\Livewire\PendingApprovalsList;
use ArcheeNic\PermissionRegistry\Livewire\PendingRevocationsDashboard;
use ArcheeNic\PermissionRegistry\Livewire\PermissionDependencies;
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
use ArcheeNic\PermissionRegistry\Events\AfterPermissionGranted;
use ArcheeNic\PermissionRegistry\Events\AfterPermissionRevoked;
use ArcheeNic\PermissionRegistry\Events\ApprovalCompleted;
use ArcheeNic\PermissionRegistry\Events\ApprovalRequested;
use ArcheeNic\PermissionRegistry\Events\VirtualUserGroupChanged;
use ArcheeNic\PermissionRegistry\Events\VirtualUserPositionChanged;
use ArcheeNic\PermissionRegistry\Facades\PermissionRegistry;
use ArcheeNic\PermissionRegistry\Listeners\HandleVirtualUserGroupChanged;
use ArcheeNic\PermissionRegistry\Listeners\HandleVirtualUserPositionChanged;
use ArcheeNic\PermissionRegistry\Listeners\SendApprovalDecisionNotification;
use ArcheeNic\PermissionRegistry\Listeners\SendApprovalRequestedNotification;
use ArcheeNic\PermissionRegistry\Listeners\SendPermissionGrantedNotification;
use ArcheeNic\PermissionRegistry\Listeners\SendPermissionRevokedNotification;
use ArcheeNic\PermissionRegistry\Contracts\UserToVirtualUserResolver;
use ArcheeNic\PermissionRegistry\Middleware\CheckPermission;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        $this->app->bind(UserToVirtualUserResolver::class, function ($app) {
            $resolverClass = config('permission-registry.user_resolver');
            return $app->make($resolverClass);
        });
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
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'permission-registry');
        $this->loadJsonTranslationsFrom(__DIR__ . '/Lang');

        // Регистрация middleware
        Route::aliasMiddleware('permission', CheckPermission::class);

        // Регистрация Livewire компонентов
        if (class_exists(Livewire::class)) {
            $this->registerLivewireComponents();
        }

        // Регистрация команд
        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireApprovalRequestsCommand::class,
            ]);
        }

        // Регистрация Blade компонентов
        $this->registerBladeComponents();

        // Регистрация слушателей событий
        $this->registerEventListeners();

        // Регистрация Gate'ов
        $this->registerGates();
    }

    protected function loadRoutes(): void
    {
        // Web routes
        Route::prefix('permission-registry')
            ->name('permission-registry::')
            ->middleware(config('permission-registry.middlewares', ['web', 'auth']))
            ->group(__DIR__ . '/Routes/web.php');

        // API routes
        if (file_exists(__DIR__ . '/Routes/api.php')) {
            Route::prefix('api')
                ->middleware(['throttle:60,1'])
                ->group(__DIR__ . '/Routes/api.php');
        }
    }

    protected function registerLivewireComponents(): void
    {
        // Регистрация Livewire компонентов
        Livewire::component('permission-registry::permissions-list', PermissionsList::class);
        Livewire::component('permission-registry::permission-dependencies', PermissionDependencies::class);
        Livewire::component('permission-registry::user-permissions', UserPermissions::class);
        Livewire::component('permission-registry::users-management', UsersManagement::class);
        Livewire::component('permission-registry::fields-list', FieldsList::class);
        Livewire::component('permission-registry::groups-list', GroupsList::class);
        Livewire::component('permission-registry::positions-list', PositionsList::class);
        Livewire::component('permission-registry::approval-policy-manager', ApprovalPolicyManager::class);
        Livewire::component('permission-registry::pending-approvals-list', PendingApprovalsList::class);
        Livewire::component('permission-registry::my-permissions', MyPermissions::class);
        Livewire::component('permission-registry::my-requests', MyRequests::class);
        Livewire::component('permission-registry::manual-tasks-list', ManualTasksList::class);
        Livewire::component('permission-registry::attestations-list', AttestationsList::class);
        Livewire::component('permission-registry::pending-revocations-dashboard', PendingRevocationsDashboard::class);
    }

    protected function registerBladeComponents(): void
    {
        // Регистрация Blade компонентов с префиксом pr
        Blade::component('permission-registry::components.flash-message', 'pr::flash-message');
        Blade::component('permission-registry::components.debug-panel', 'pr::debug-panel');
        Blade::component('permission-registry::components.trigger-status-item', 'pr::trigger-status-item');
        Blade::component('permission-registry::components.completed-triggers-panel', 'pr::completed-triggers-panel');
        Blade::component('permission-registry::components.processing-triggers-panel', 'pr::processing-triggers-panel');
        Blade::component('permission-registry::components.permission-status-badge', 'pr::permission-status-badge');
        Blade::component('permission-registry::components.permissions-table', 'pr::permissions-table');
        Blade::component('permission-registry::components.user-card', 'pr::user-card');
        Blade::component('permission-registry::components.users-table', 'pr::users-table');
        Blade::component('permission-registry::components.progress-panel', 'pr::progress-panel');
        Blade::component('permission-registry::components.positions-section', 'pr::positions-section');
        Blade::component('permission-registry::components.groups-section', 'pr::groups-section');
        Blade::component('permission-registry::components.approval-status-badge', 'pr::approval-status-badge');
        Blade::component('permission-registry::components.field-hint', 'pr::field-hint');
        Blade::component('permission-registry::components.field-hint', 'perm::field-hint');
    }

    protected function registerGates(): void
    {
        Gate::define('permission-registry.manage', function ($user) {
            $resolver = app(UserToVirtualUserResolver::class);
            $checker = app(PermissionChecker::class);
            $virtualUserId = $resolver->resolve($user->id);
            if (!$virtualUserId) {
                return false;
            }
            return $checker->hasPermission($virtualUserId, 'permission-registry', 'manage');
        });

        Gate::define('permission-registry.approve', function ($user) {
            $resolver = app(UserToVirtualUserResolver::class);
            $checker = app(PermissionChecker::class);
            $virtualUserId = $resolver->resolve($user->id);
            if (!$virtualUserId) {
                return false;
            }
            return $checker->hasPermission($virtualUserId, 'permission-registry', 'manage')
                || $checker->hasPermission($virtualUserId, 'permission-registry', 'approve');
        });

        Gate::define('permission-registry.self-service', function ($user) {
            $resolver = app(UserToVirtualUserResolver::class);
            $virtualUserId = $resolver->resolve($user->id);
            return $virtualUserId !== null;
        });

        Gate::define('permission-registry.resolve-hr-conflict', function ($user) {
            $resolver = app(UserToVirtualUserResolver::class);
            $checker = app(PermissionChecker::class);
            $virtualUserId = $resolver->resolve($user->id);
            if (! $virtualUserId) {
                return false;
            }

            return $checker->hasPermission($virtualUserId, 'permission-registry', 'manage')
                || $checker->hasPermission($virtualUserId, 'hr.trigger', 'resolve_conflict');
        });
    }

    protected function registerEventListeners(): void
    {
        Event::listen(
            VirtualUserPositionChanged::class,
            HandleVirtualUserPositionChanged::class
        );

        Event::listen(
            VirtualUserGroupChanged::class,
            HandleVirtualUserGroupChanged::class
        );

        Event::listen(ApprovalRequested::class, SendApprovalRequestedNotification::class);
        Event::listen(ApprovalCompleted::class, SendApprovalDecisionNotification::class);
        Event::listen(AfterPermissionGranted::class, SendPermissionGrantedNotification::class);
        Event::listen(AfterPermissionRevoked::class, SendPermissionRevokedNotification::class);
    }
}