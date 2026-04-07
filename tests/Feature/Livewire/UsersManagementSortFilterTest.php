<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature\Livewire;

use App\Models\User;
use ArcheeNic\PermissionRegistry\Enums\EmployeeCategory;
use ArcheeNic\PermissionRegistry\Enums\VirtualUserStatus;
use ArcheeNic\PermissionRegistry\Livewire\UsersManagement;
use ArcheeNic\PermissionRegistry\Models\PermissionGroup;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Livewire\Livewire;

class UsersManagementSortFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->withPersonalTeam()->create());
    }

    // ── Sorting ──────────────────────────────────────────────

    public function test_default_sort_is_created_at_desc(): void
    {
        $old = VirtualUser::factory()->create([
            'name' => 'Old User',
            'created_at' => now()->subDays(5),
        ]);
        $new = VirtualUser::factory()->create([
            'name' => 'New User',
            'created_at' => now(),
        ]);

        $component = Livewire::test(UsersManagement::class);

        $users = $component->viewData('users');
        $this->assertEquals($new->id, $users->first()->id);
        $this->assertEquals($old->id, $users->last()->id);
    }

    public function test_sort_by_name_asc(): void
    {
        VirtualUser::factory()->create(['name' => 'Charlie']);
        VirtualUser::factory()->create(['name' => 'Alice']);
        VirtualUser::factory()->create(['name' => 'Bob']);

        $component = Livewire::test(UsersManagement::class)
            ->set('sortField', 'name')
            ->set('sortDirection', 'asc');

        $names = $component->viewData('users')->pluck('name')->toArray();
        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $names);
    }

    public function test_sort_by_name_desc(): void
    {
        VirtualUser::factory()->create(['name' => 'Charlie']);
        VirtualUser::factory()->create(['name' => 'Alice']);
        VirtualUser::factory()->create(['name' => 'Bob']);

        $component = Livewire::test(UsersManagement::class)
            ->set('sortField', 'name')
            ->set('sortDirection', 'desc');

        $names = $component->viewData('users')->pluck('name')->toArray();
        $this->assertEquals(['Charlie', 'Bob', 'Alice'], $names);
    }

    public function test_sort_by_status(): void
    {
        VirtualUser::factory()->create([
            'name' => 'Deactivated',
            'status' => VirtualUserStatus::DEACTIVATED->value,
        ]);
        VirtualUser::factory()->create([
            'name' => 'Active',
            'status' => VirtualUserStatus::ACTIVE->value,
        ]);

        $component = Livewire::test(UsersManagement::class)
            ->set('sortField', 'status')
            ->set('sortDirection', 'asc');

        $statuses = $component->viewData('users')->pluck('status')->map(fn ($s) => $s->value)->toArray();
        $this->assertEquals($statuses, collect($statuses)->sort()->values()->toArray());
    }

    public function test_invalid_sort_field_falls_back_to_default(): void
    {
        $old = VirtualUser::factory()->create(['created_at' => now()->subDays(5)]);
        $new = VirtualUser::factory()->create(['created_at' => now()]);

        $component = Livewire::test(UsersManagement::class)
            ->set('sortField', 'nonexistent_column');

        $users = $component->viewData('users');
        $this->assertEquals($new->id, $users->first()->id);
    }

    public function test_invalid_sort_direction_falls_back_to_desc(): void
    {
        $old = VirtualUser::factory()->create(['created_at' => now()->subDays(5)]);
        $new = VirtualUser::factory()->create(['created_at' => now()]);

        $component = Livewire::test(UsersManagement::class)
            ->set('sortDirection', 'invalid');

        $users = $component->viewData('users');
        $this->assertEquals($new->id, $users->first()->id);
    }

    // ── Filters ──────────────────────────────────────────────

    public function test_filter_by_status_active(): void
    {
        VirtualUser::factory()->create(['status' => VirtualUserStatus::ACTIVE->value]);
        VirtualUser::factory()->create(['status' => VirtualUserStatus::DEACTIVATED->value]);

        $component = Livewire::test(UsersManagement::class)
            ->set('filterStatus', 'active');

        $users = $component->viewData('users');
        $this->assertCount(1, $users);
        $this->assertEquals(VirtualUserStatus::ACTIVE, $users->first()->status);
    }

    public function test_filter_by_status_deactivated(): void
    {
        VirtualUser::factory()->create(['status' => VirtualUserStatus::ACTIVE->value]);
        VirtualUser::factory()->create(['status' => VirtualUserStatus::DEACTIVATED->value]);

        $component = Livewire::test(UsersManagement::class)
            ->set('filterStatus', 'deactivated');

        $users = $component->viewData('users');
        $this->assertCount(1, $users);
        $this->assertEquals(VirtualUserStatus::DEACTIVATED, $users->first()->status);
    }

    public function test_filter_by_employee_category_staff(): void
    {
        VirtualUser::factory()->create(['employee_category' => EmployeeCategory::STAFF->value]);
        VirtualUser::factory()->create(['employee_category' => EmployeeCategory::CONTRACTOR->value]);

        $component = Livewire::test(UsersManagement::class)
            ->set('filterCategory', 'staff');

        $users = $component->viewData('users');
        $this->assertCount(1, $users);
        $this->assertEquals(EmployeeCategory::STAFF, $users->first()->employee_category);
    }

    public function test_filter_by_employee_category_contractor(): void
    {
        VirtualUser::factory()->create(['employee_category' => EmployeeCategory::STAFF->value]);
        VirtualUser::factory()->create(['employee_category' => EmployeeCategory::CONTRACTOR->value]);

        $component = Livewire::test(UsersManagement::class)
            ->set('filterCategory', 'contractor');

        $users = $component->viewData('users');
        $this->assertCount(1, $users);
        $this->assertEquals(EmployeeCategory::CONTRACTOR, $users->first()->employee_category);
    }

    public function test_filter_by_group(): void
    {
        $group = PermissionGroup::factory()->create();
        $inGroup = VirtualUser::factory()->create(['name' => 'In Group']);
        $inGroup->groups()->attach($group);
        VirtualUser::factory()->create(['name' => 'No Group']);

        $component = Livewire::test(UsersManagement::class)
            ->set('filterGroup', $group->id);

        $users = $component->viewData('users');
        $this->assertCount(1, $users);
        $this->assertEquals('In Group', $users->first()->name);
    }

    public function test_filter_empty_returns_all(): void
    {
        VirtualUser::factory()->count(3)->create();

        $component = Livewire::test(UsersManagement::class)
            ->set('filterStatus', '')
            ->set('filterCategory', '')
            ->set('filterGroup', '');

        $users = $component->viewData('users');
        $this->assertCount(3, $users);
    }

    // ── Combinations ─────────────────────────────────────────

    public function test_filter_status_and_category_combined(): void
    {
        VirtualUser::factory()->create([
            'status' => VirtualUserStatus::ACTIVE->value,
            'employee_category' => EmployeeCategory::STAFF->value,
        ]);
        VirtualUser::factory()->create([
            'status' => VirtualUserStatus::ACTIVE->value,
            'employee_category' => EmployeeCategory::CONTRACTOR->value,
        ]);
        VirtualUser::factory()->create([
            'status' => VirtualUserStatus::DEACTIVATED->value,
            'employee_category' => EmployeeCategory::STAFF->value,
        ]);

        $component = Livewire::test(UsersManagement::class)
            ->set('filterStatus', 'active')
            ->set('filterCategory', 'staff');

        $users = $component->viewData('users');
        $this->assertCount(1, $users);
        $this->assertEquals(VirtualUserStatus::ACTIVE, $users->first()->status);
        $this->assertEquals(EmployeeCategory::STAFF, $users->first()->employee_category);
    }

    public function test_filter_with_search_combined(): void
    {
        VirtualUser::factory()->create([
            'name' => 'Alice Active',
            'status' => VirtualUserStatus::ACTIVE->value,
        ]);
        VirtualUser::factory()->create([
            'name' => 'Alice Deactivated',
            'status' => VirtualUserStatus::DEACTIVATED->value,
        ]);
        VirtualUser::factory()->create([
            'name' => 'Bob Active',
            'status' => VirtualUserStatus::ACTIVE->value,
        ]);

        $component = Livewire::test(UsersManagement::class)
            ->set('search', 'Alice')
            ->set('filterStatus', 'active');

        $users = $component->viewData('users');
        $this->assertCount(1, $users);
        $this->assertEquals('Alice Active', $users->first()->name);
    }

    public function test_filter_and_sort_combined(): void
    {
        VirtualUser::factory()->create([
            'name' => 'Zara',
            'status' => VirtualUserStatus::ACTIVE->value,
        ]);
        VirtualUser::factory()->create([
            'name' => 'Anna',
            'status' => VirtualUserStatus::ACTIVE->value,
        ]);
        VirtualUser::factory()->create([
            'name' => 'Deactivated',
            'status' => VirtualUserStatus::DEACTIVATED->value,
        ]);

        $component = Livewire::test(UsersManagement::class)
            ->set('filterStatus', 'active')
            ->set('sortField', 'name')
            ->set('sortDirection', 'asc');

        $names = $component->viewData('users')->pluck('name')->toArray();
        $this->assertEquals(['Anna', 'Zara'], $names);
    }

    // ── Pagination reset ─────────────────────────────────────

    public function test_page_resets_on_filter_change(): void
    {
        VirtualUser::factory()->count(15)->create([
            'status' => VirtualUserStatus::ACTIVE->value,
        ]);

        $component = Livewire::test(UsersManagement::class)
            ->call('gotoPage', 2)
            ->set('filterStatus', 'active');

        $users = $component->viewData('users');
        $this->assertEquals(1, $users->currentPage());
    }

    public function test_page_resets_on_sort_change(): void
    {
        VirtualUser::factory()->count(15)->create();

        $component = Livewire::test(UsersManagement::class)
            ->call('gotoPage', 2)
            ->set('sortField', 'name');

        $users = $component->viewData('users');
        $this->assertEquals(1, $users->currentPage());
    }

    public function test_per_page_changes_results_count(): void
    {
        VirtualUser::factory()->count(20)->create();

        $component = Livewire::test(UsersManagement::class)
            ->set('perPage', 25);

        $users = $component->viewData('users');
        $this->assertCount(20, $users);
    }

    public function test_invalid_per_page_falls_back_to_default(): void
    {
        VirtualUser::factory()->count(15)->create();

        $component = Livewire::test(UsersManagement::class)
            ->set('perPage', 99);

        $users = $component->viewData('users');
        $this->assertCount(12, $users);
    }

    // ── Reset ────────────────────────────────────────────────

    public function test_reset_filters_clears_all(): void
    {
        $component = Livewire::test(UsersManagement::class)
            ->set('filterStatus', 'active')
            ->set('filterCategory', 'staff')
            ->set('filterGroup', 1)
            ->set('sortField', 'name')
            ->set('sortDirection', 'asc')
            ->set('search', 'test')
            ->call('resetFilters');

        $component->assertSet('filterStatus', '');
        $component->assertSet('filterCategory', '');
        $component->assertSet('filterGroup', '');
        $component->assertSet('sortField', 'created_at');
        $component->assertSet('sortDirection', 'desc');
        $component->assertSet('search', '');
    }
}
