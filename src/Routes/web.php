<?php

use ArcheeNic\PermissionRegistry\Controllers\PermissionController;
use ArcheeNic\PermissionRegistry\Controllers\PermissionFieldController;
use ArcheeNic\PermissionRegistry\Controllers\PermissionGroupController;
use ArcheeNic\PermissionRegistry\Controllers\PositionController;
use ArcheeNic\PermissionRegistry\Controllers\UserController;

// Главная страница модуля
Route::get('', function () {
    return view('permission-registry::index');
})->name('index');

// Маршруты для прав доступа
Route::prefix('permissions')->group(function () {
    Route::get('', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('create', [PermissionController::class, 'create'])->name('permissions.create');
    Route::post('', [PermissionController::class, 'store'])->name('permissions.store');
    Route::get('{permission}', [PermissionController::class, 'show'])->name('permissions.show');
    Route::get('{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
    Route::put('{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
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

// Новые маршруты для управления правами пользователей
Route::prefix('users')->group(function () {
    Route::get('{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
    Route::post('{user}/permissions/grant', [UserController::class, 'grantPermission'])->name('users.permissions.grant');
    Route::delete('{user}/permissions/{permission}', [UserController::class, 'revokePermission'])->name('users.permissions.revoke');
});
