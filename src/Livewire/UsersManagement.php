<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Actions\AssignUserGroupAction;
use ArcheeNic\PermissionRegistry\Actions\AssignUserPositionAction;
use ArcheeNic\PermissionRegistry\Actions\GrantPermissionAction;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\UserGroup;
use ArcheeNic\PermissionRegistry\Models\UserPosition;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use Livewire\Component;
use Livewire\WithPagination;

class UsersManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedUser = null;
    public $selectedUserId = null;
    public $selectedPosition = ''; // изменить с null на пустую строку
    public $selectedGroup = null;

    // Новые поля для создания пользователя
    public $showCreateForm = false;
    public $newUserName = '';
    public $newUserEmail = '';

    protected $listeners = ['refreshUsers' => '$refresh'];

    public $permissionSearch = '';
    public $selectedPermissions = [];
    public $permissionFields = [];
    public $expandedPermissionFields = [];


    // Правила валидации для нового пользователя
    protected $rules = [
        'newUserName' => 'required|min:3',
        'newUserEmail' => 'required|email|unique:virtual_users,email',
    ];

    public function assignGroup()
    {
        $this->validate([
            'selectedUserId' => 'required',
            'selectedGroup' => 'required',
        ]);

        $action = app(AssignUserGroupAction::class);
        $action->handle($this->selectedUserId, $this->selectedGroup);

        $this->selectedUser = VirtualUser::with(['positions', 'groups'])->find($this->selectedUserId);
        $this->selectedGroup = null;

        // Перезагружаем данные пользователя после изменения групп
        $this->selectUser($this->selectedUserId);

        $this->dispatch('refreshUsers');
    }

    public function removeGroup($groupId)
    {
        $action = app(AssignUserGroupAction::class);
        $action->remove($this->selectedUserId, $groupId);

        $this->selectedUser = VirtualUser::with(['positions', 'groups'])->find($this->selectedUserId);

        // Перезагружаем данные пользователя после изменения групп
        $this->selectUser($this->selectedUserId);

        $this->dispatch('refreshUsers');
    }

    public function toggleCreateForm()
    {
        $this->showCreateForm = !$this->showCreateForm;
        if ($this->showCreateForm) {
            $this->newUserName = '';
            $this->newUserEmail = '';
        }
    }

    public function createUser()
    {
        $this->validate();

        $user = VirtualUser::create([
            'name' => $this->newUserName,
            'email' => $this->newUserEmail,
        ]);

        $this->newUserName = '';
        $this->newUserEmail = '';
        $this->showCreateForm = false;
        $this->dispatch('refreshUsers');

        // Выбираем созданного пользователя
        $this->selectUser($user->id);
    }

    public function getUsersProperty()
    {
        return VirtualUser::when($this->search, function ($query) {
            $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%");
        })
            ->paginate(10);
    }

    public function getGroupsProperty()
    {
        if (!$this->selectedUserId) {
            return collect();
        }

        $userGroupIds = UserGroup::where('user_id', $this->selectedUserId)
            ->pluck('permission_group_id');

        return PermissionGroup::whereNotIn('id', $userGroupIds)
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('permission-registry::livewire.users-management', [
            'users' => $this->users
        ]);
    }

    // Добавьте этот метод в класс UsersManagement
    public function isPositionSelected(): bool
    {
        return !empty($this->selectedPosition);
    }

    public function assignPosition()
    {
        $this->validate([
            'selectedUserId' => 'required',
            'selectedPosition' => 'required',
        ]);

        $action = app(AssignUserPositionAction::class);
        $action->handle($this->selectedUserId, $this->selectedPosition);

        $this->selectedUser = VirtualUser::with(['positions', 'groups'])->find($this->selectedUserId);
        $this->selectedPosition = null;

        // Перезагружаем данные пользователя после изменения должности
        $this->selectUser($this->selectedUserId);

        $this->dispatch('refreshUsers');
    }

    public function removePosition($positionId)
    {
        if (!$this->selectedUserId) {
            return;
        }

        $action = app(AssignUserPositionAction::class);
        $action->remove($this->selectedUserId, $positionId);

        $this->selectedUser = VirtualUser::with(['positions', 'groups'])->find($this->selectedUserId);

        // Перезагружаем данные пользователя после изменения должности
        $this->selectUser($this->selectedUserId);

        $this->dispatch('refreshUsers');
    }

    public function togglePermissionFields($permissionId)
    {
        if (isset($this->expandedPermissionFields[$permissionId])) {
            unset($this->expandedPermissionFields[$permissionId]);
        } else {
            $this->expandedPermissionFields[$permissionId] = true;
        }
    }

    public function getPositionsProperty()
    {
        if (!$this->selectedUserId) {
            return collect();
        }

        $assignedPositionIds = UserPosition::where('user_id', $this->selectedUserId)
            ->pluck('position_id')
            ->toArray();

        return Position::whereNotIn('id', $assignedPositionIds)
            ->orderBy('name')
            ->get();
    }

    public function getAvailablePermissionsProperty()
    {
        if (!$this->selectedUserId) {
            return collect();
        }

        // Получаем ID зависимых прав
        $dependentPermissionIds = $this->dependentPermissions->pluck('id')->toArray();

        // Получаем все права, исключая зависимые
        return Permission::with('fields')
            ->whereNotIn('id', $dependentPermissionIds)
            ->when($this->permissionSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->permissionSearch}%")
                        ->orWhere('service', 'like', "%{$this->permissionSearch}%")
                        ->orWhere('description', 'like', "%{$this->permissionSearch}%");
                });
            })
            ->orderBy('service')
            ->orderBy('name')
            ->get();
    }

    public $expandedDependentPermissionFields = [];
    public $dependentPermissionFields = [];

    public function toggleDependentPermissionFields($permissionId)
    {
        if (isset($this->expandedDependentPermissionFields[$permissionId])) {
            unset($this->expandedDependentPermissionFields[$permissionId]);
        } else {
            $this->expandedDependentPermissionFields[$permissionId] = true;
        }
    }

    public function getDependentPermissionsProperty()
    {
        if (!$this->selectedUserId) {
            return collect();
        }

        $result = collect();

        // Получаем права из должностей пользователя
        $userPositions = $this->selectedUser->positions;
        foreach ($userPositions as $position) {
            // Прямые права должности
            foreach ($position->permissions as $permission) {
                // Получаем поля для данного разрешения
                $fields = $this->getFieldsForPermission($permission);

                $result->push([
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'service' => $permission->service,
                    'description' => $permission->description,
                    'source_type' => 'position',
                    'source_id' => $position->id,
                    'source_name' => $position->name,
                    'has_fields' => count($fields) > 0,
                    'fields' => $fields
                ]);
            }

            // Права из групп должности
            foreach ($position->groups as $group) {
                foreach ($group->permissions as $permission) {
                    // Получаем поля для данного разрешения
                    $fields = $this->getFieldsForPermission($permission);

                    $result->push([
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'service' => $permission->service,
                        'description' => $permission->description,
                        'source_type' => 'position_group',
                        'source_id' => $group->id,
                        'source_name' => $group->name,
                        'has_fields' => count($fields) > 0,
                        'fields' => $fields
                    ]);
                }
            }
        }

        // Получаем права из групп пользователя
        $userGroups = $this->selectedUser->groups;
        foreach ($userGroups as $group) {
            foreach ($group->permissions as $permission) {
                // Получаем поля для данного разрешения
                $fields = $this->getFieldsForPermission($permission);

                $result->push([
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'service' => $permission->service,
                    'description' => $permission->description,
                    'source_type' => 'group',
                    'source_id' => $group->id,
                    'source_name' => $group->name,
                    'has_fields' => count($fields) > 0,
                    'fields' => $fields
                ]);
            }
        }

        // Удаляем дубликаты по id
        return $result->unique('id')->values();
    }

    protected function getFieldsForPermission($permission)
    {
        $fields = [];

        // Проверяем, есть ли уже выданное разрешение для текущего пользователя (включая неактивные)
        $grantedPermission = GrantedPermission::where('user_id', $this->selectedUserId)
            ->where('permission_id', $permission->id)
            ->with('fieldValues.field')
            ->first();

        // Загружаем все поля разрешения
        foreach ($permission->fields as $field) {
            $value = '';

            // Если разрешение уже выдано, берем значение из него
            if ($grantedPermission) {
                $fieldValue = $grantedPermission->fieldValues->first(function ($item) use ($field) {
                    return $item->permission_field_id == $field->id;
                });

                if ($fieldValue) {
                    $value = $fieldValue->value;

                    // Сохраняем значение в массив для формы
                    if (!isset($this->dependentPermissionFields[$permission->id])) {
                        $this->dependentPermissionFields[$permission->id] = [];
                    }
                    $this->dependentPermissionFields[$permission->id][$field->id] = $value;
                }
            }

            $fields[] = [
                'id' => $field->id,
                'name' => $field->name,
                'default_value' => $field->default_value,
                'value' => $value
            ];
        }

        return $fields;
    }

    public $dependentSelectedPermissions = [];

    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
        $this->selectedUser = VirtualUser::with(['positions', 'groups'])->find($userId);
        $this->selectedPosition = null;
        $this->selectedGroup = null;

        // Очистка предыдущих данных
        $this->selectedPermissions = [];
        $this->permissionFields = [];
        $this->dependentSelectedPermissions = [];
        $this->dependentPermissionFields = [];

        // Загрузка всех прав пользователя (включая неактивные)
        $allUserPermissions = GrantedPermission::where('user_id', $userId)
            ->with(['permission', 'fieldValues.field'])
            ->get();

        // Получаем активные права из всех прав
        $activePermissions = $allUserPermissions->where('enabled', true);

        // Отмечаем активные права
        foreach ($activePermissions as $granted) {
            $this->selectedPermissions[$granted->permission_id] = true;
        }

        // Загружаем значения полей для всех прав (включая неактивные)
        foreach ($allUserPermissions as $granted) {
            foreach ($granted->fieldValues as $fieldValue) {
                if (!isset($this->permissionFields[$granted->permission_id])) {
                    $this->permissionFields[$granted->permission_id] = [];
                }
                $this->permissionFields[$granted->permission_id][$fieldValue->permission_field_id] = $fieldValue->value;
            }
        }

        // Загрузка статуса для зависимых прав
        // Получаем все доступные зависимые права
        $dependentPermissions = $this->getDependentPermissionsProperty();

        foreach ($dependentPermissions as $permission) {
            // Проверяем, есть ли это право у пользователя в базе (включая неактивные)
            $existingPermission = $allUserPermissions->firstWhere('permission_id', $permission['id']);

            if ($existingPermission) {
                // Если право существует, устанавливаем статус исходя из поля enabled
                $this->dependentSelectedPermissions[$permission['id']] = $existingPermission->enabled;

                // Загружаем значения полей
                foreach ($existingPermission->fieldValues as $fieldValue) {
                    if (!isset($this->dependentPermissionFields[$permission['id']])) {
                        $this->dependentPermissionFields[$permission['id']] = [];
                    }
                    $this->dependentPermissionFields[$permission['id']][$fieldValue->permission_field_id] = $fieldValue->value;
                }
            } else {
                // Если право не существует в базе, устанавливаем статус в false
                $this->dependentSelectedPermissions[$permission['id']] = false;
            }
        }
    }

    public function saveUserPermissions()
    {
        if (!$this->selectedUserId) {
            return;
        }

        $grantAction = app(GrantPermissionAction::class);

        // Получаем ID всех доступных прав
        $availablePermissionIds = $this->availablePermissions->pluck('id')->toArray();

        // Получаем текущие права пользователя
        $userPermissions = GrantedPermission::where('user_id', $this->selectedUserId)->get();

        foreach ($availablePermissionIds as $permId) {
            $isSelected = isset($this->selectedPermissions[$permId]) && $this->selectedPermissions[$permId];
            $existingPermission = $userPermissions->firstWhere('permission_id', $permId);

            if ($isSelected) {
                // Если право выбрано, выдаем его с полями или обновляем существующее
                $fieldValues = $this->permissionFields[$permId] ?? [];
                $meta = $existingPermission ? $existingPermission->meta : [];
                $expiresAt = $existingPermission ? $existingPermission->expires_at : null;

                $grantAction->handle($this->selectedUserId, $permId, $fieldValues, $meta, $expiresAt);
            } elseif ($existingPermission) {
                // Если право не выбрано, но существует - деактивируем его
                $existingPermission->update(['enabled' => false]);
            }
        }

        // Сохраняем статус и поля зависимых прав
        foreach ($this->dependentSelectedPermissions as $permId => $isEnabled) {
            $existingPermission = $userPermissions->firstWhere('permission_id', $permId);

            if ($isEnabled) {
                // Если право включено, сохраняем его с полями или обновляем существующее
                $fieldValues = $this->dependentPermissionFields[$permId] ?? [];
                $meta = $existingPermission ? $existingPermission->meta : [];
                $expiresAt = $existingPermission ? $existingPermission->expires_at : null;

                $grantAction->handle($this->selectedUserId, $permId, $fieldValues, $meta, $expiresAt);
            } elseif ($existingPermission) {
                // Если право выключено, но существует - деактивируем его
                $existingPermission->update(['enabled' => false]);
            }
        }

        // Перезагружаем данные пользователя
        $this->selectUser($this->selectedUserId);

        session()->flash('message', __('permission-registry::Permissions updated successfully'));
    }
}
