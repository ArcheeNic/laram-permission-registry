<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Actions\ProcessAccessAttestationDecisionAction;
use ArcheeNic\PermissionRegistry\Contracts\UserToVirtualUserResolver;
use ArcheeNic\PermissionRegistry\Enums\AttestationStatus;
use ArcheeNic\PermissionRegistry\Models\AccessAttestation;
use Livewire\Component;
use Livewire\WithPagination;

class AttestationsList extends Component
{
    use WithPagination;

    public ?int $currentUserId = null;

    public string $statusFilter = '';

    public int $perPage = 15;

    public ?int $selectedAttestationId = null;

    public string $decision = '';

    public string $comment = '';

    public ?string $flashMessage = null;

    public ?string $flashError = null;

    protected $queryString = [
        'statusFilter' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];

    private const ALLOWED_PER_PAGE = [10, 15, 25, 50];

    private const TERMINAL_DECISIONS = ['confirmed', 'rejected'];

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

    public function openDecisionModal(int $attestationId, string $decision): void
    {
        $this->selectedAttestationId = $attestationId;
        $this->decision = $decision;
        $this->comment = '';
    }

    public function closeModal(): void
    {
        $this->selectedAttestationId = null;
        $this->decision = '';
        $this->comment = '';
    }

    public function submitDecision(): void
    {
        $virtualUserId = $this->resolveCurrentVirtualUserId();
        if (! $virtualUserId) {
            $this->flashError = __('permission-registry::governance.user_not_mapped');

            return;
        }
        if (! $this->selectedAttestationId || ! $this->decision) {
            return;
        }

        if (! in_array($this->decision, self::TERMINAL_DECISIONS, true)) {
            return;
        }

        $attestation = AccessAttestation::find($this->selectedAttestationId);
        if (! $attestation) {
            return;
        }

        try {
            $status = AttestationStatus::from($this->decision);
            $comment = $this->comment !== '' ? mb_substr($this->comment, 0, 2000) : null;

            app(ProcessAccessAttestationDecisionAction::class)->handle(
                $attestation,
                $status,
                $virtualUserId,
                $comment
            );

            $this->flashMessage = __('permission-registry::governance.decision_saved');
            $this->closeModal();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->flashError = $e->validator->errors()->first();
        } catch (\Exception $e) {
            $this->flashError = $e->getMessage();
        }
    }

    public function getAttestationsProperty()
    {
        $validStatuses = array_map(fn ($s) => $s->value, AttestationStatus::cases());
        $statusFilter = in_array($this->statusFilter, $validStatuses, true) ? $this->statusFilter : '';

        return AccessAttestation::query()
            ->when($statusFilter !== '', function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->with(['grantedPermission.permission', 'grantedPermission.user', 'decider'])
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
        return view('permission-registry::livewire.attestations-list', [
            'attestations' => $this->attestations,
            'statuses' => AttestationStatus::cases(),
        ]);
    }
}
