<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Actions\ConfirmManualProvisionAction;
use ArcheeNic\PermissionRegistry\Contracts\UserToVirtualUserResolver;
use ArcheeNic\PermissionRegistry\Enums\EvidenceType;
use ArcheeNic\PermissionRegistry\Enums\ManualTaskStatus;
use ArcheeNic\PermissionRegistry\Models\ManualProvisionTask;
use Livewire\Component;
use Livewire\WithPagination;

class ManualTasksList extends Component
{
    use WithPagination;

    public ?int $currentUserId = null;

    public string $statusFilter = '';

    public int $perPage = 15;

    public ?int $selectedTaskId = null;

    public string $evidenceType = 'comment';

    public string $evidenceValue = '';

    public ?string $flashMessage = null;

    public ?string $flashError = null;

    protected $queryString = [
        'statusFilter' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    private const ALLOWED_PER_PAGE = [10, 15, 25, 50];

    public function mount(?int $currentUserId = null): void
    {
        $this->currentUserId = $currentUserId;
    }

    public function updatedPerPage($value): void
    {
        if (! in_array((int) $value, self::ALLOWED_PER_PAGE, true)) {
            $this->perPage = 15;
        }
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function selectTask(int $taskId): void
    {
        $this->selectedTaskId = $taskId;
        $this->evidenceType = 'comment';
        $this->evidenceValue = '';
    }

    public function closeModal(): void
    {
        $this->selectedTaskId = null;
        $this->evidenceValue = '';
    }

    public function completeTask(): void
    {
        $virtualUserId = $this->resolveCurrentVirtualUserId();
        if (! $virtualUserId) {
            $this->flashError = __('permission-registry::governance.user_not_mapped');

            return;
        }
        if (! $this->selectedTaskId) {
            return;
        }

        $task = ManualProvisionTask::find($this->selectedTaskId);
        if (! $task) {
            return;
        }

        try {
            $evidenceData = [];
            if ($this->evidenceValue !== '') {
                $validType = EvidenceType::tryFrom($this->evidenceType)?->value ?? EvidenceType::COMMENT->value;
                $evidenceData = [
                    'type' => $validType,
                    'value' => mb_substr($this->evidenceValue, 0, 2000),
                    'meta' => null,
                ];
            }

            app(ConfirmManualProvisionAction::class)->handle(
                $task,
                $virtualUserId,
                $evidenceData
            );

            $this->flashMessage = __('permission-registry::governance.complete_task').' ✓';
            $this->closeModal();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->flashError = $e->validator->errors()->first();
        } catch (\Exception $e) {
            $this->flashError = $e->getMessage();
        }
    }

    public function getTasksProperty()
    {
        $validStatuses = array_map(fn ($s) => $s->value, ManualTaskStatus::cases());
        $statusFilter = in_array($this->statusFilter, $validStatuses, true) ? $this->statusFilter : '';

        return ManualProvisionTask::query()
            ->when($statusFilter !== '', function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->with(['grantedPermission.permission', 'grantedPermission.user', 'assignee'])
            ->orderByDesc('created_at')
            ->paginate(in_array($this->perPage, self::ALLOWED_PER_PAGE, true) ? $this->perPage : 15);
    }

    private function resolveCurrentVirtualUserId(): ?int
    {
        $userId = auth()->id();
        if (! $userId) {
            return null;
        }

        return app(UserToVirtualUserResolver::class)->resolve($userId);
    }

    public function render()
    {
        return view('permission-registry::livewire.manual-tasks-list', [
            'tasks' => $this->tasks,
            'statuses' => ManualTaskStatus::cases(),
            'evidenceTypes' => EvidenceType::cases(),
        ]);
    }
}
