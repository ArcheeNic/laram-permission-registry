<?php

namespace ArcheeNic\PermissionRegistry\Tests\Feature;

use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

class FieldHintComponentTest extends TestCase
{
    public function test_field_hint_component_renders_title_and_description_with_pr_alias(): void
    {
        $title = 'Help title';
        $description = 'Helpful description text';

        $html = Blade::render('<x-pr::field-hint :title="$title" :description="$description" />', [
            'title' => $title,
            'description' => $description,
        ]);

        $this->assertStringContainsString($title, $html);
        $this->assertStringContainsString($description, $html);
    }

    public function test_field_hint_component_renders_title_and_description_with_perm_alias(): void
    {
        $title = 'Permission title';
        $description = 'Permission description';

        $html = Blade::render('<x-perm::field-hint :title="$title" :description="$description" />', [
            'title' => $title,
            'description' => $description,
        ]);

        $this->assertStringContainsString($title, $html);
        $this->assertStringContainsString($description, $html);
    }
}
