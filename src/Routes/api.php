<?php

use ArcheeNic\PermissionRegistry\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('permission-registry')
    ->name('permission-registry::api.')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        // Управление пользователями
        Route::post('users', [UserApiController::class, 'store'])->name('users.store');

        // Управление правами пользователей
        Route::prefix('users/{user}')->group(function () {
            Route::post('permissions', [UserApiController::class, 'grantPermission'])->name('users.permissions.grant');
            Route::delete('permissions/{permission}', [UserApiController::class, 'revokePermission'])->name('users.permissions.revoke');

            // Управление должностями пользователей
            Route::post('positions', [UserApiController::class, 'assignPosition'])->name('users.positions.assign');
            Route::delete('positions/{position}', [UserApiController::class, 'revokePosition'])->name('users.positions.revoke');

            // Управление группами пользователей
            Route::post('groups', [UserApiController::class, 'assignGroup'])->name('users.groups.assign');
            Route::delete('groups/{group}', [UserApiController::class, 'revokeGroup'])->name('users.groups.revoke');
        });
    });
