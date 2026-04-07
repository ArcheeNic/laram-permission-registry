<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Livewire;

use ArcheeNic\PermissionRegistry\Livewire\PositionsList;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\Position;
use ArcheeNic\PermissionRegistry\Tests\TestCase;

class PositionsListTest extends TestCase
{
    public function test_nested_positions_have_permissions_count_loaded(): void
    {
        $topPosition = Position::factory()->create();
        $middlePosition = Position::factory()->create([
            'parent_id' => $topPosition->id,
        ]);
        $leafPosition = Position::factory()->create([
            'parent_id' => $middlePosition->id,
        ]);

        $leafPosition->permissions()->attach(Permission::factory()->create());

        $component = app(PositionsList::class);
        $view = $component->render();
        $positions = $view->getData()['positions'];

        $loadedTopPosition = $positions->first();
        $loadedMiddlePosition = $loadedTopPosition->children->first();
        $loadedLeafPosition = $loadedMiddlePosition->children->first();

        $this->assertSame(1, $loadedLeafPosition->permissions_count);
    }
}
