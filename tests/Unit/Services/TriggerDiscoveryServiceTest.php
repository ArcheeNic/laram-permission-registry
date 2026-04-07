<?php

namespace ArcheeNic\PermissionRegistry\Tests\Unit\Services;

use ArcheeNic\PermissionRegistry\Services\TriggerDiscoveryService;
use ArcheeNic\PermissionRegistry\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class TriggerDiscoveryServiceTest extends TestCase
{
    private TriggerDiscoveryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TriggerDiscoveryService();
    }

    public function test_discover_returns_empty_array_when_namespace_config_is_missing(): void
    {
        Config::set('triggers.namespace', null);
        Config::set('triggers.directory', app_path('Triggers'));

        $result = $this->service->discover();

        $this->assertSame([], $result);
    }

    public function test_discover_returns_empty_array_when_directory_config_is_missing(): void
    {
        Config::set('triggers.namespace', 'App\\Triggers');
        Config::set('triggers.directory', null);

        $result = $this->service->discover();

        $this->assertSame([], $result);
    }

    public function test_discover_returns_empty_array_when_directory_does_not_exist(): void
    {
        Config::set('triggers.namespace', 'App\\Triggers');
        Config::set('triggers.directory', '/non/existent/path/' . uniqid());

        $result = $this->service->discover();

        $this->assertSame([], $result);
    }
}
