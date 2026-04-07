<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Actions\GetPendingRevocationsAction;
use ArcheeNic\PermissionRegistry\Actions\ProcessFireRevocationsAction;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class PendingRevocationsDashboard extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $employeeCategory = '';

    public int $perPage = 15;

    public ?int $expandedUserId = null;

    public ?string $flashMessage = null;

    public ?string $flashError = null;

    protected $queryString = [
        'employeeCategory' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    private const ALLOWED_PER_PAGE = [10, 15, 25, 50];

    public function mount(): void
    {
        $this->authorize('permission-registry.manage');
    }

    public function updatedEmployeeCategory(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage($value): void
    {
        $this->perPage = in_array((int) $value, self::ALLOWED_PER_PAGE, true) ? (int) $value : 15;
        $this->resetPage();
    }

    public function toggleExpanded(int $userId): void
    {
        $this->expandedUserId = $this->expandedUserId === $userId ? null : $userId;
    }

    public function revokeAutomated(int $userId): void
    {
        $this->authorize('permission-registry.manage');
        if (! $this->isDeactivatedUser($userId)) {
            $this->flashError = __('permission-registry::messages.user_must_be_deactivated');
            $this->flashMessage = null;

            return;
        }

        $result = app(ProcessFireRevocationsAction::class)->handle(
            userId: $userId,
            dispatchAutomatedRevokes: true,
            createManualTasks: false,
            includeAutoGranted: true
        );

        if ($result['automated_revokes_dispatched'] === 0) {
            $this->flashError = __('permission-registry::messages.no_automated_revocations_pending');
            $this->flashMessage = null;

            return;
        }

        $this->flashMessage = __('permission-registry::messages.automated_revocations_dispatched', [
            'count' => $result['automated_revokes_dispatched'],
        ]);
        $this->flashError = null;
    }

    public function createManualTasks(int $userId): void
    {
        $this->authorize('permission-registry.manage');
        if (! $this->isDeactivatedUser($userId)) {
            $this->flashError = __('permission-registry::messages.user_must_be_deactivated');
            $this->flashMessage = null;

            return;
        }

        $result = app(ProcessFireRevocationsAction::class)->handle(
            userId: $userId,
            dispatchAutomatedRevokes: false,
            createManualTasks: true,
            includeAutoGranted: true
        );

        if ($result['manual_tasks_created'] === 0) {
            $this->flashError = __('permission-registry::messages.no_manual_revocations_pending');
            $this->flashMessage = null;

            return;
        }

        $this->flashMessage = __('permission-registry::messages.manual_revocation_tasks_created', [
            'count' => $result['manual_tasks_created'],
        ]);
        $this->flashError = null;
    }

    public function getRowsProperty()
    {
        return app(GetPendingRevocationsAction::class)->handle(
            employeeCategory: $this->employeeCategory,
            perPage: $this->perPage
        );
    }

    public function getSummaryProperty(): array
    {
        return app(GetPendingRevocationsAction::class)->getSummary(
            employeeCategory: $this->employeeCategory
        );
    }

    public function render()
    {
        return view('permission-registry::livewire.pending-revocations-dashboard', [
            'rows' => $this->rows,
            'summary' => $this->summary,
            'employeeCategories' => EmployeeCategory::cases(),
        ]);
    }

    private function isDeactivatedUser(int $userId): bool
    {
        return VirtualUser::query()
            ->where('id', $userId)
            ->where('status', VirtualUserStatus::DEACTIVATED->value)
            ->exists();
    }
}

