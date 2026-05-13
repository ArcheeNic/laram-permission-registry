<?php

namespace ArcheeNic\PermissionRegistry\Commands;

use ArcheeNic\PermissionRegistry\Database\Seeders\SystemPermissionSeeder;
use ArcheeNic\PermissionRegistry\Models\GrantedPermission;
use ArcheeNic\PermissionRegistry\Models\Permission;
use ArcheeNic\PermissionRegistry\Models\VirtualUser;
use Illuminate\Console\Command;

class BootstrapAdminCommand extends Command
{
    protected $signature = 'permission-registry:bootstrap-admin
                            {virtual_user : ID, email or name fragment of the virtual user}
                            {--grant=* : Permission names to grant (default: manage, approve, self-service)}';

    protected $description = 'Seed system permissions and grant them to a virtual user (recovery from fail-closed lockout)';

    public function handle(): int
    {
        (new SystemPermissionSeeder())->setContainer($this->getLaravel())->run();

        $virtualUser = $this->resolveVirtualUser((string) $this->argument('virtual_user'));
        if (!$virtualUser) {
            return self::FAILURE;
        }

        $grants = $this->option('grant') ?: ['manage', 'approve', 'self-service'];

        foreach ($grants as $name) {
            $permission = Permission::query()
                ->where('service', 'permission-registry')
                ->where('name', $name)
                ->first();

            if (!$permission) {
                $this->warn("Permission permission-registry/$name not found, skipping.");
                continue;
            }

            $granted = GrantedPermission::findForUserPermissionResource($virtualUser->id, $permission->id, null);
            if ($granted) {
                $granted->update(['enabled' => true, 'status' => 'granted', 'granted_at' => $granted->granted_at ?: now()]);
                $this->line("  refreshed grant permission-registry/$name to virtual_user_id={$virtualUser->id}");
            } else {
                GrantedPermission::create([
                    'virtual_user_id' => $virtualUser->id,
                    'permission_id' => $permission->id,
                    'enabled' => true,
                    'status' => 'granted',
                    'granted_at' => now(),
                ]);
                $this->info("  granted permission-registry/$name to virtual_user_id={$virtualUser->id}");
            }
        }

        return self::SUCCESS;
    }

    private function resolveVirtualUser(string $arg): ?VirtualUser
    {
        if (ctype_digit($arg)) {
            $vu = VirtualUser::query()->find((int) $arg);
            if ($vu) {
                return $vu;
            }
        }

        $userModelClass = config('permission-registry.user_model');
        if ($userModelClass) {
            $user = $userModelClass::query()->where('email', $arg)->first();
            if ($user) {
                $vu = VirtualUser::query()->where('user_id', $user->id)->first();
                if ($vu) {
                    return $vu;
                }
                $this->error("App user '$arg' (id={$user->id}) found, but no VirtualUser is linked (virtual_users.user_id).");
                return null;
            }
        }

        $candidates = VirtualUser::query()
            ->where('name', 'like', "%$arg%")
            ->orderBy('id')
            ->limit(10)
            ->get();

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        if ($candidates->isEmpty()) {
            $this->error("No virtual user matches '$arg'. Pass id, app-user email or unique name fragment.");
            return null;
        }

        $this->error("Ambiguous: $arg matches multiple virtual users:");
        foreach ($candidates as $c) {
            $this->line(sprintf('  id=%d name=%s user_id=%s', $c->id, $c->name, $c->user_id ?? 'null'));
        }
        return null;
    }
}
