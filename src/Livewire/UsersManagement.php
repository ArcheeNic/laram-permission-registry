<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Livewire\Concerns\HasFlashMessages;
use ArcheeNic\PermissionRegistry\Livewire\Concerns\ManagesBulkSelection;
use ArcheeNic\PermissionRegistry\Livewire\Concerns\ManagesGlobalFields;
use ArcheeNic\PermissionRegistry\Livewire\Concerns\ManagesGroups;
use ArcheeNic\PermissionRegistry\Livewire\Concerns\ManagesManualGrant;
use ArcheeNic\PermissionRegistry\Livewire\Concerns\ManagesPermissions;
use ArcheeNic\PermissionRegistry\Livewire\Concerns\ManagesPermissionStatus;
use ArcheeNic\PermissionRegistry\Livewire\Concerns\ManagesAppUserLink;
use ArcheeNic\PermissionRegistry\Livewire\Concerns\ManagesHiringConflicts;
use ArcheeNic\PermissionRegistry\Livewire\Concerns\ManagesHiring;
use ArcheeNic\PermissionRegistry\Livewire\Concerns\ManagesPositions;
use ArcheeNic\PermissionRegistry\Livewire\Concerns\ManagesUserCreation;
use ArcheeNic\PermissionRegistry\Enums\HrTriggerExecutionStatus;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class UsersManagement extends Component
{
    use WithPagination;
    use AuthorizesRequests;
    use HasFlashMessages;
    use ManagesBulkSelection;
    use ManagesUserCreation;
    use ManagesAppUserLink;
    use ManagesHiring;
    use ManagesHiringConflicts;
    use ManagesGroups;
    use ManagesPositions;
    use ManagesGlobalFields;
    use ManagesManualGrant;
    use ManagesPermissions;
    use ManagesPermissionStatus;

    private const ALLOWED_SORT_FIELDS = [
        'id', 'name', 'created_at', 'updated_at', 'status', 'employee_category',
    ];

    private const ALLOWED_PER_PAGE = [12, 25, 50];

    public $search = '';
    public $viewMode = 'table';
    public $selectedUser = null;
    public $selectedUserId = null;
    public $showEditModal = false;

    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $filterStatus = '';
    public $filterCategory = '';
    public $filterGroup = '';
    public $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'filterStatus' => ['except' => ''],
        'filterCategory' => ['except' => ''],
        'filterGroup' => ['except' => ''],
        'perPage' => ['except' => 12],
    ];

    protected $listeners = ['refreshUsers' => '$refresh'];

    public function updatingSearch(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingSortField(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingSortDirection(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingFilterGroup(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function updatedPerPage($value): void
    {
        if (! in_array((int) $value, self::ALLOWED_PER_PAGE, true)) {
            $this->perPage = 12;
        }
    }

    public function updatingPage(): void
    {
        $this->clearBulkSelection();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'sortField', 'sortDirection', 'filterStatus', 'filterCategory', 'filterGroup']);
        $this->clearBulkSelection();
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->filterStatus !== ''
            || $this->filterCategory !== ''
            || $this->filterGroup !== ''
            || $this->search !== '';
    }

    public function getAvailableGroupsProperty()
    {
        return PermissionGroup::orderBy('name')->get();
    }

    public function getAvailablePositionsProperty()
    {
        return Position::orderBy('name')->get();
    }

    public function getUsersProperty()
    {
        $sortField = in_array($this->sortField, self::ALLOWED_SORT_FIELDS, true)
            ? $this->sortField
            : 'created_at';

        $sortDirection = in_array($this->sortDirection, ['asc', 'desc'], true)
            ? $this->sortDirection
            : 'desc';

        $perPage = in_array((int) $this->perPage, self::ALLOWED_PER_PAGE, true)
            ? (int) $this->perPage
            : 12;

        $filterStatus = in_array($this->filterStatus, ['active', 'deactivated'], true)
            ? $this->filterStatus
            : '';

        $filterCategory = in_array($this->filterCategory, ['staff', 'contractor'], true)
            ? $this->filterCategory
            : '';

        return VirtualUser::with([
            'positions.parent.parent.parent.parent',
            'groups',
            'fieldValues.field',
            'grantedPermissions' => function ($query) {
                $query->where('enabled', true)
                    ->with('permission')
                    ->orderByDesc('granted_at')
                    ->orderByDesc('id');
            },
        ])
            ->withCount([
                'hrTriggerExecutionLogs as pending_hr_conflicts_count' => function ($query) {
                    $query->where('status', HrTriggerExecutionStatus::AWAITING_RESOLUTION->value);
                },
            ])
            ->when($this->search, function ($query) {
                $search = str_replace(['%', '_'], ['\%', '\_'], mb_substr(trim($this->search), 0, 255));
                $isNumericSearch = ctype_digit($search);

                $query->where(function ($searchQuery) use ($search, $isNumericSearch) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->when($isNumericSearch, function ($idQuery) use ($search) {
                            $idQuery->orWhere('id', (int) $search);
                        })
                        ->orWhere('meta->email', 'like', "%{$search}%")
                        ->orWhereHas('fieldValues', function ($fieldValuesQuery) use ($search) {
                            $fieldValuesQuery->where('value', 'like', "%{$search}%")
                                ->whereHas('field', function ($fieldQuery) {
                                    $fieldQuery->where(function ($nameQuery) {
                                        $nameQuery->where('name', 'like', '%email%')
                                            ->orWhere('name', 'like', '%e-mail%')
                                            ->orWhere('name', 'like', '%почт%');
                                    });
                                });
                        });
                });
            })
            ->when($filterStatus !== '', fn ($q) => $q->where('status', $filterStatus))
            ->when($filterCategory !== '', fn ($q) => $q->where('employee_category', $filterCategory))
            ->when($this->filterGroup !== '' && (int) $this->filterGroup > 0, function ($q) {
                $q->whereHas('groups', fn ($gq) => $gq->where('permission_groups.id', (int) $this->filterGroup));
            })
            ->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($perPage);
    }

    public function render()
    {
        return view('permission-registry::livewire.users-management', [
            'users' => $this->users,
            'availableGroups' => $this->availableGroups,
            'availablePositions' => $this->availablePositions,
            'hasActiveFilters' => $this->hasActiveFilters(),
        ]);
    }

    public function openEditModal($userId)
    {
        $this->selectUser($userId);
        $this->showEditModal = true;
        $this->isProcessing = false;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
    }

    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
        $this->selectedUser = VirtualUser::with([
            'positions.parent.permissions',
            'positions.parent.groups.permissions',
            'positions.parent.parent.permissions',
            'positions.parent.parent.groups.permissions',
            'positions.permissions',
            'positions.groups.permissions',
            'groups.permissions',
            'fieldValues',
        ])->find($userId);

        if (! $this->selectedUser) {
            $this->selectedUserId = null;
            $this->showEditModal = false;

            return;
        }

        $this->selectedHireCategory = (string) ($this->selectedUser->employee_category?->value ?? $this->selectedHireCategory);

        $this->resetUserState();
        $this->loadGlobalFieldValues();
        $this->initializePermissionFields();
        $this->loadUserPermissions();
        $this->loadDependentPermissionStatuses();
        $this->loadPermissionsWithErrors();
        $this->linkedAppUserId = $this->selectedUser->user_id;
        $this->refreshSelectedUserHrConflicts();
    }

    private function resetUserState(): void
    {
        $this->selectedPosition = null;
        $this->selectedGroup = null;
        $this->selectedPermissions = [];
        $this->permissionFields = [];
        $this->dependentSelectedPermissions = [];
        $this->dependentPermissionFields = [];
        $this->globalFields = [];
        $this->permissionStatuses = [];
        $this->dependentPermissionStatuses = [];
        $this->hasPendingPermissions = false;
        $this->completedPermissionsWithErrors = [];
        $this->dependentPermissionErrors = [];
        $this->dirtyDependentPermissionSelections = [];
    }

    public function updatedDependentSelectedPermissions(mixed $value, string $key): void
    {
        if ($key === '') {
            return;
        }

        $this->dirtyDependentPermissionSelections[(int) $key] = true;
    }

    private function loadGlobalFieldValues(): void
    {
        foreach ($this->selectedUser->fieldValues as $fieldValue) {
            $this->globalFields[$fieldValue->permission_field_id] = $fieldValue->value;
        }
    }

    private function initializePermissionFields(): void
    {
        $availablePermissions = $this->getAvailablePermissionsProperty();
        foreach ($availablePermissions as $permission) {
            foreach ($permission->fields as $field) {
                if ($field->is_global && isset($this->globalFields[$field->id])) {
                    $this->permissionFields[$permission->id][$field->id] = $this->globalFields[$field->id];
                }
            }
        }
    }

    private function loadUserPermissions(): void
    {
        $allUserPermissions = GrantedPermission::where('virtual_user_id', $this->selectedUserId)
            ->with(['permission', 'fieldValues.field'])
            ->get();

        $activePermissions = $allUserPermissions->where('enabled', true);

        foreach ($activePermissions as $granted) {
            $this->selectedPermissions[$granted->permission_id] = true;
        }

        foreach ($allUserPermissions as $granted) {
            foreach ($granted->permission->fields as $field) {
                if ($field->is_global && isset($this->globalFields[$field->id])) {
                    $this->permissionFields[$granted->permission_id][$field->id] = $this->globalFields[$field->id];
                }
            }

            foreach ($granted->fieldValues as $fieldValue) {
                if (!$fieldValue->field->is_global) {
                    $this->permissionFields[$granted->permission_id][$fieldValue->permission_field_id] = $fieldValue->value;
                }
            }

            if ($granted->enabled) {
                $this->permissionStatuses[$granted->permission_id] = [
                    'status' => $granted->status ?? 'granted',
                    'status_message' => $granted->status_message,
                ];

                if (in_array($granted->status, ['pending', 'granting', 'revoking'])) {
                    $this->hasPendingPermissions = true;
                }
            }
        }
    }

    private function loadDependentPermissionStatuses(): void
    {
        $allUserPermissions = GrantedPermission::where('virtual_user_id', $this->selectedUserId)
            ->with(['permission', 'fieldValues.field'])
            ->get();

        $dependentPermissions = $this->getDependentPermissionsProperty();

        foreach ($dependentPermissions as $permission) {
            $existingPermission = $allUserPermissions->firstWhere('permission_id', $permission['id']);

            if ($existingPermission) {
                $this->dependentSelectedPermissions[$permission['id']] = $existingPermission->enabled;

                if (isset($permission['fields'])) {
                    foreach ($permission['fields'] as $field) {
                        $this->dependentPermissionFields[$permission['id']][$field['id']] = $field['value'];
                    }
                }

                if ($existingPermission->enabled) {
                    $this->dependentPermissionStatuses[$permission['id']] = [
                        'status' => $existingPermission->status ?? 'granted',
                        'status_message' => $existingPermission->status_message,
                    ];

                    if (in_array($existingPermission->status, ['pending', 'granting', 'revoking'])) {
                        $this->hasPendingPermissions = true;
                    }
                }
            } else {
                $this->dependentSelectedPermissions[$permission['id']] = false;
            }
        }
    }
}
