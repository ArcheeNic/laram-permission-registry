<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Actions;

use ArcheeNic\PermissionRegistry\Actions\CreateVirtualUserAction;
use ArcheeNic\PermissionRegistry\Events\UserCreated;
use ArcheeNic\PermissionRegistry\Models\PermissionField;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class CreateVirtualUserActionTest extends TestCase
{
    private PermissionField $firstNameField;

    private PermissionField $lastNameField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firstNameField = PermissionField::create([
            'name' => 'Имя',
            'is_global' => true,
            'required_on_user_create' => true,
        ]);

        $this->lastNameField = PermissionField::create([
            'name' => 'Фамилия',
            'is_global' => true,
            'required_on_user_create' => true,
        ]);
    }

    public function test_creates_user_without_fields(): void
    {
        Event::fake();

        $action = app(CreateVirtualUserAction::class);
        $user = $action->handle();

        $this->assertInstanceOf(VirtualUser::class, $user);
        $this->assertStringStartsWith('User #', $user->name);
        $this->assertDatabaseHas('virtual_users', ['id' => $user->id]);
    }

    public function test_creates_user_with_fields_saved(): void
    {
        Event::fake();

        $action = app(CreateVirtualUserAction::class);
        $user = $action->handle([
            $this->firstNameField->id => 'Иван',
            $this->lastNameField->id => 'Петров',
        ]);

        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->firstNameField->id,
            'value' => 'Иван',
        ]);
        $this->assertDatabaseHas('virtual_user_field_values', [
            'virtual_user_id' => $user->id,
            'permission_field_id' => $this->lastNameField->id,
            'value' => 'Петров',
        ]);
    }

    public function test_display_name_updated_from_fields(): void
    {
        Event::fake();

        config([
            'permission-registry.display_name_template' => "{{$this->firstNameField->id}} {{$this->lastNameField->id}}",
        ]);

        $action = app(CreateVirtualUserAction::class);
        $user = $action->handle([
            $this->firstNameField->id => 'Иван',
            $this->lastNameField->id => 'Петров',
        ]);

        $this->assertSame('Иван Петров', $user->name);
    }

    public function test_user_created_event_dispatched(): void
    {
        Event::fake([UserCreated::class]);

        $action = app(CreateVirtualUserAction::class);
        $user = $action->handle();

        Event::assertDispatched(UserCreated::class, function (UserCreated $event) use ($user) {
            return $event->userId === $user->id;
        });
    }

    public function test_email_extracted_from_email_field(): void
    {
        Event::fake([UserCreated::class]);

        $emailField = PermissionField::create([
            'name' => 'email',
            'is_global' => true,
            'required_on_user_create' => false,
        ]);

        $action = app(CreateVirtualUserAction::class);
        $action->handle([
            $this->firstNameField->id => 'Иван',
            $emailField->id => 'ivan@example.com',
        ]);

        Event::assertDispatched(UserCreated::class, function (UserCreated $event) {
            return $event->email === 'ivan@example.com';
        });
    }
}
