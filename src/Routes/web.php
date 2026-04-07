<?php

use ArcheeNic\PermissionRegistry\Controllers\PermissionController;
use ArcheeNic\PermissionRegistry\Controllers\PermissionDependencyController;
use ArcheeNic\PermissionRegistry\Controllers\PermissionFieldController;
use ArcheeNic\PermissionRegistry\Controllers\PermissionGroupController;
use ArcheeNic\PermissionRegistry\Controllers\HrEventTriggerAssignmentController;
use ArcheeNic\PermissionRegistry\Controllers\PermissionTriggerAssignmentController;
use ArcheeNic\PermissionRegistry\Controllers\PermissionTriggerController;
use ArcheeNic\PermissionRegistry\Controllers\PositionController;
use ArcheeNic\PermissionRegistry\Controllers\UserController;

// Главная страница модуля
Route::get('', function () {
    return view('permission-registry::index');
})->name('index')->middleware('can:permission-registry.manage');

// Админские маршруты — require manage
Route::middleware('can:permission-registry.manage')->group(function () {
    // Маршруты для прав доступа
    Route::prefix('permissions')->group(function () {
        Route::get('', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('create', [PermissionController::class, 'create'])->name('permissions.create');
        Route::post('', [PermissionController::class, 'store'])->name('permissions.store');
        Route::post('{permission}/copy', [PermissionController::class, 'copy'])->name('permissions.copy');
        Route::get('{permission}', [PermissionController::class, 'show'])->name('permissions.show');
        Route::get('{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        // Триггеры для права
        Route::get('{permission}/triggers', [PermissionTriggerAssignmentController::class, 'index'])->name('permissions.triggers');
        Route::get('{permission}/triggers/config-fields/{trigger}', [PermissionTriggerAssignmentController::class, 'configFields'])->name('permissions.triggers.config-fields');
        Route::post('{permission}/triggers', [PermissionTriggerAssignmentController::class, 'store'])->name('permissions.triggers.store');
        Route::put('{permission}/triggers/{assignment}', [PermissionTriggerAssignmentController::class, 'update'])->name('permissions.triggers.update');
        Route::delete('{permission}/triggers/{assignment}', [PermissionTriggerAssignmentController::class, 'destroy'])->name('permissions.triggers.destroy');
        Route::post('{permission}/triggers/reorder', [PermissionTriggerAssignmentController::class, 'reorder'])->name('permissions.triggers.reorder');

        // Зависимости права
        Route::get('{permission}/dependencies', [PermissionDependencyController::class, 'index'])->name('permissions.dependencies');
        Route::post('{permission}/dependencies', [PermissionDependencyController::class, 'store'])->name('permissions.dependencies.store');
        Route::put('{permission}/dependencies/{dependency}', [PermissionDependencyController::class, 'update'])->name('permissions.dependencies.update');
        Route::delete('{permission}/dependencies/{dependency}', [PermissionDependencyController::class, 'destroy'])->name('permissions.dependencies.destroy');
    });

    // Маршруты для реестра триггеров
    Route::prefix('triggers')->group(function () {
        Route::get('', [PermissionTriggerController::class, 'index'])->name('triggers.index');
        Route::get('create', [PermissionTriggerController::class, 'create'])->name('triggers.create');
        Route::post('', [PermissionTriggerController::class, 'store'])->name('triggers.store');
        Route::get('{permissionTrigger}/edit', [PermissionTriggerController::class, 'edit'])->name('triggers.edit');
        Route::put('{permissionTrigger}', [PermissionTriggerController::class, 'update'])->name('triggers.update');
        Route::delete('{permissionTrigger}', [PermissionTriggerController::class, 'destroy'])->name('triggers.destroy');

        // API endpoints для автокомплита
        Route::get('api/discover', [PermissionTriggerController::class, 'discover'])->name('triggers.api.discover');
        Route::get('api/metadata', [PermissionTriggerController::class, 'metadata'])->name('triggers.api.metadata');
    });

    // HR-триггеры для событий найма/увольнения
    Route::prefix('hr-triggers')->group(function () {
        Route::get('', [HrEventTriggerAssignmentController::class, 'index'])->name('hr-triggers.index');
        Route::get('config-fields/{trigger}', [HrEventTriggerAssignmentController::class, 'configFields'])->name('hr-triggers.config-fields');
        Route::post('', [HrEventTriggerAssignmentController::class, 'store'])->name('hr-triggers.store');
        Route::put('{assignment}', [HrEventTriggerAssignmentController::class, 'update'])->name('hr-triggers.update');
        Route::delete('{assignment}', [HrEventTriggerAssignmentController::class, 'destroy'])->name('hr-triggers.destroy');
    });

    // Маршруты для полей доступа
    Route::prefix('fields')->group(function () {
        Route::get('', [PermissionFieldController::class, 'index'])->name('fields.index');
        Route::get('create', [PermissionFieldController::class, 'create'])->name('fields.create');
        Route::post('', [PermissionFieldController::class, 'store'])->name('fields.store');
        Route::get('{field}/edit', [PermissionFieldController::class, 'edit'])->name('fields.edit');
        Route::put('{field}', [PermissionFieldController::class, 'update'])->name('fields.update');
        Route::delete('{field}', [PermissionFieldController::class, 'destroy'])->name('fields.destroy');
    });

    // Маршруты для групп доступа
    Route::prefix('groups')->group(function () {
        Route::get('', [PermissionGroupController::class, 'index'])->name('groups.index');
        Route::get('create', [PermissionGroupController::class, 'create'])->name('groups.create');
        Route::post('', [PermissionGroupController::class, 'store'])->name('groups.store');
        Route::get('{group}', [PermissionGroupController::class, 'show'])->name('groups.show');
        Route::get('{group}/edit', [PermissionGroupController::class, 'edit'])->name('groups.edit');
        Route::put('{group}', [PermissionGroupController::class, 'update'])->name('groups.update');
        Route::delete('{group}', [PermissionGroupController::class, 'destroy'])->name('groups.destroy');
    });

    // Маршруты для должностей
    Route::prefix('positions')->group(function () {
        Route::get('', [PositionController::class, 'index'])->name('positions.index');
        Route::get('create', [PositionController::class, 'create'])->name('positions.create');
        Route::post('', [PositionController::class, 'store'])->name('positions.store');
        Route::get('{position}', [PositionController::class, 'show'])->name('positions.show');
        Route::get('{position}/edit', [PositionController::class, 'edit'])->name('positions.edit');
        Route::put('{position}', [PositionController::class, 'update'])->name('positions.update');
        Route::delete('{position}', [PositionController::class, 'destroy'])->name('positions.destroy');
    });

    // Маршруты для пользователей
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

    Route::prefix('users')->group(function () {
        Route::get('{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
        Route::post('{user}/permissions/grant', [UserController::class, 'grantPermission'])->name('users.permissions.grant');
        Route::delete('{user}/permissions/{permission}', [UserController::class, 'revokePermission'])->name('users.permissions.revoke');
    });
});

// Маршруты для подтверждений — require approve
Route::middleware('can:permission-registry.approve')->group(function () {
    Route::prefix('approvals')->group(function () {
        Route::get('', function () {
            return view('permission-registry::approvals.index');
        })->name('approvals.index');
    });
});

// Governance маршруты — require manage
Route::middleware('can:permission-registry.manage')->group(function () {
    Route::get('manual-tasks', function () {
        return view('permission-registry::manual-tasks.index');
    })->name('manual-tasks.index');

    Route::get('pending-revocations', function () {
        return view('permission-registry::pending-revocations.index');
    })->name('pending-revocations.index');

    Route::get('attestations', function () {
        return view('permission-registry::attestations.index');
    })->name('attestations.index');
});

// Self-service маршруты
Route::middleware('can:permission-registry.self-service')->prefix('my')->name('my.')->group(function () {
    Route::get('permissions', function () {
        return view('permission-registry::my.permissions');
    })->name('permissions');

    Route::get('requests', function () {
        return view('permission-registry::my.requests');
    })->name('requests');
});
