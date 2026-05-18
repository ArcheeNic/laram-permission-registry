<?php

namespace ArcheeNic\PermissionRegistry\Livewire;

use ArcheeNic\PermissionRegistry\Actions\SyncResourcesAction;
use ArcheeNic\PermissionRegistry\Models\PermissionResource;
use ArcheeNic\PermissionRegistry\Services\ResourceSyncerRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class ResourcesList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $service = '';

    public string $kind = '';

    public string $presence = 'present';

    public string $ignored = 'active';

    public int $perPage = 25;

    public bool $showFormModal = false;

    public ?int $editingResourceId = null;

    public string $formService = '';

    public string $formKind = '';

    public string $formExternalId = '';

    public string $formName = '';

    public string $formMetadata = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'service' => ['except' => ''],
        'kind' => ['except' => ''],
        'presence' => ['except' => 'present'],
        'ignored' => ['except' => 'active'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingService(): void
    {
        $this->resetPage();
    }

    public function updatingKind(): void
    {
        $this->resetPage();
    }

    public function updatingPresence(): void
    {
        $this->resetPage();
    }

    public function updatingIgnored(): void
    {
        $this->resetPage();
    }

    public function toggleIgnore(int $resourceId): void
    {
        $this->authorize('permission-registry.manage');

        $resource = PermissionResource::query()->find($resourceId);
        if (! $resource) {
            session()->flash('error', __('permission-registry::Resource not found'));

            return;
        }

        $resource->is_ignored = ! $resource->is_ignored;
        $resource->save();

        session()->flash('success', $resource->is_ignored
            ? __('permission-registry::Resource ignored')
            : __('permission-registry::Resource un-ignored'));
    }

    public function openCreate(): void
    {
        $this->authorize('permission-registry.manage');
        $this->resetForm();
        $this->editingResourceId = null;
        $this->showFormModal = true;
    }

    public function openEdit(int $resourceId): void
    {
        $this->authorize('permission-registry.manage');
        $resource = PermissionResource::query()->find($resourceId);
        if (! $resource) {
            session()->flash('error', __('permission-registry::Resource not found'));

            return;
        }
        $this->editingResourceId = $resource->id;
        $this->formService = $resource->service;
        $this->formKind = $resource->kind;
        $this->formExternalId = $resource->external_id;
        $this->formName = $resource->name;
        $this->formMetadata = $this->encodeMetadata($resource->metadata);
        $this->showFormModal = true;
    }

    public function closeForm(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function saveResource(SyncResourcesAction $action): void
    {
        $this->authorize('permission-registry.manage');

        $validated = $this->validate([
            'formService' => 'required|string|max:100',
            'formKind' => 'required|string|max:100',
            'formExternalId' => 'required|string|max:255',
            'formName' => 'required|string|max:255',
            'formMetadata' => 'nullable|string|max:2000',
        ]);

        $metadata = $this->parseMetadata($validated['formMetadata'] ?? '');

        try {
            if ($this->editingResourceId !== null) {
                $resource = PermissionResource::query()->find($this->editingResourceId);
                if (! $resource) {
                    session()->flash('error', __('permission-registry::Resource not found'));

                    return;
                }
                $resource->fill([
                    PermissionResource::SERVICE => $validated['formService'],
                    PermissionResource::KIND => $validated['formKind'],
                    PermissionResource::EXTERNAL_ID => $validated['formExternalId'],
                    PermissionResource::NAME => $validated['formName'],
                    PermissionResource::METADATA => $metadata,
                ])->save();
                session()->flash('success', __('permission-registry::Resource saved'));
            } else {
                $action->registerManually(
                    $validated['formService'],
                    $validated['formKind'],
                    $validated['formExternalId'],
                    $validated['formName'],
                    $metadata,
                );
                session()->flash('success', __('permission-registry::Resource saved'));
            }
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->closeForm();
    }

    public function deleteResource(int $resourceId): void
    {
        $this->authorize('permission-registry.manage');
        $resource = PermissionResource::query()->withCount('grantedPermissions')->find($resourceId);
        if (! $resource) {
            session()->flash('error', __('permission-registry::Resource not found'));

            return;
        }
        if ($resource->granted_permissions_count > 0) {
            session()->flash('error', __('permission-registry::Cannot delete resource with active grants'));

            return;
        }
        $resource->delete();
        session()->flash('success', __('permission-registry::Resource deleted'));
    }

    private function resetForm(): void
    {
        $this->editingResourceId = null;
        $this->formService = $this->service;
        $this->formKind = $this->kind;
        $this->formExternalId = '';
        $this->formName = '';
        $this->formMetadata = '';
        $this->resetErrorBag();
    }

    private function parseMetadata(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        $result = [];
        foreach (preg_split('/\r?\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '' || ! str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $result[trim($k)] = trim($v);
        }

        return $result === [] ? null : $result;
    }

    private function encodeMetadata(mixed $metadata): string
    {
        if (! is_array($metadata) || $metadata === []) {
            return '';
        }
        $lines = [];
        foreach ($metadata as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $lines[] = $k.'='.(string) $v;
            } else {
                return (string) json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        }

        return implode("\n", $lines);
    }

    public function sync(string $service, ResourceSyncerRegistry $registry, SyncResourcesAction $action): void
    {
        $this->authorize('permission-registry.manage');

        $syncers = $service === '' ? $registry->all() : $registry->forService($service);
        if ($syncers === []) {
            session()->flash('error', __('permission-registry::No syncers registered for this service'));

            return;
        }

        $totals = ['created' => 0, 'updated' => 0, 'disappeared' => 0, 'reappeared' => 0, 'errors' => 0];
        foreach ($syncers as $syncer) {
            try {
                $report = $action->handle($syncer);
                $totals['created'] += $report->created;
                $totals['updated'] += $report->updated;
                $totals['disappeared'] += $report->disappeared;
                $totals['reappeared'] += $report->reappeared;
                $totals['errors'] += count($report->errors);
            } catch (\Throwable $e) {
                session()->flash('error', $syncer->service().'/'.$syncer->kind().': '.$e->getMessage());

                return;
            }
        }

        session()->flash('success', sprintf(
            __('permission-registry::Sync done: created %d, updated %d, disappeared %d, reappeared %d, errors %d'),
            $totals['created'],
            $totals['updated'],
            $totals['disappeared'],
            $totals['reappeared'],
            $totals['errors'],
        ));
    }

    public function syncOne(int $resourceId, ResourceSyncerRegistry $registry, SyncResourcesAction $action): void
    {
        $this->authorize('permission-registry.manage');

        $resource = PermissionResource::query()->find($resourceId);
        if (! $resource) {
            session()->flash('error', __('permission-registry::Resource not found'));

            return;
        }

        $matched = null;
        foreach ($registry->forService($resource->service) as $syncer) {
            if ($syncer->kind() === $resource->kind) {
                $matched = $syncer;
                break;
            }
        }

        if ($matched === null) {
            session()->flash('error', __('permission-registry::No syncers registered for this service'));

            return;
        }

        try {
            $report = $action->handleSelective($matched, [(string) $resource->external_id]);
        } catch (\Throwable $e) {
            session()->flash('error', $matched->service().'/'.$matched->kind().': '.$e->getMessage());

            return;
        }

        session()->flash('success', sprintf(
            __('permission-registry::Sync done: created %d, updated %d, disappeared %d, reappeared %d, errors %d'),
            $report->created,
            $report->updated,
            $report->disappeared,
            $report->reappeared,
            count($report->errors),
        ));
    }

    public function getResourcesProperty()
    {
        return PermissionResource::query()
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('external_id', 'like', "%{$this->search}%");
            }))
            ->when($this->service !== '', fn ($q) => $q->where('service', $this->service))
            ->when($this->kind !== '', fn ($q) => $q->where('kind', $this->kind))
            ->when($this->presence === 'present', fn ($q) => $q->where('present_in_source', true))
            ->when($this->presence === 'missing', fn ($q) => $q->where('present_in_source', false))
            ->when($this->ignored === 'active', fn ($q) => $q->where('is_ignored', false))
            ->when($this->ignored === 'ignored', fn ($q) => $q->where('is_ignored', true))
            ->withCount('grantedPermissions')
            ->orderBy('service')
            ->orderBy('kind')
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('permission-registry::livewire.resources-list', [
            'resources' => $this->resources,
            'services' => PermissionResource::query()->select('service')->distinct()->orderBy('service')->pluck('service'),
            'kinds' => PermissionResource::query()->select('kind')->distinct()->orderBy('kind')->pluck('kind'),
        ]);
    }
}
