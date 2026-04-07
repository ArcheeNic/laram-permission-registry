<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Contracts\UserToVirtualUserResolver;
use ArcheeNic\PermissionRegistry\Enums\ApprovalRequestStatus;
use ArcheeNic\PermissionRegistry\Enums\GrantedPermissionStatus;
use ArcheeNic\PermissionRegistry\Models\ApprovalRequest;
use Livewire\Component;

class MyRequests extends Component
{
    public ?int $currentUserId = null;
    public ?int $virtualUserId = null;
    public string $statusFilter = '';

    public ?string $flashMessage = null;
    public ?string $flashError = null;

    public function mount(?int $currentUserId = null): void
    {
        $this->currentUserId = $currentUserId;
        $resolver = app(UserToVirtualUserResolver::class);
        $this->virtualUserId = $resolver->resolve($this->currentUserId);
    }

    public function getRequestsProperty()
    {
        if (!$this->virtualUserId) {
            return collect();
        }

        $query = ApprovalRequest::with(['grantedPermission.permission', 'decisions'])
            ->where('requested_by', $this->virtualUserId);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function cancelRequest(int $requestId): void
    {
        $request = ApprovalRequest::where('id', $requestId)
            ->where('requested_by', $this->virtualUserId)
            ->where('status', ApprovalRequestStatus::PENDING->value)
            ->first();

        if (!$request) {
            return;
        }

        $request->update([
            'status' => ApprovalRequestStatus::CANCELLED->value,
            'resolved_at' => now(),
        ]);

        $grantedPermission = $request->grantedPermission;
        if ($grantedPermission) {
            $grantedPermission->update([
                'status' => GrantedPermissionStatus::REJECTED->value,
            ]);
        }

        $this->flashMessage = __('permission-registry::messages.request_cancelled');
        $this->flashError = null;
    }

    public function render()
    {
        return view('permission-registry::livewire.my-requests');
    }
}
