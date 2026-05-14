<?php

namespace ArcheeNic\PermissionRegistry\Database\Seeders;

use App\Triggers\BitbucketAddToRepoTrigger;
use App\Triggers\BitbucketRemoveFromRepoTrigger;
use App\Triggers\Bitrix24AddToDepartmentTrigger;
use App\Triggers\Bitrix24BlockUserTrigger;
use App\Triggers\Bitrix24ChangeDepartmentTrigger;
use App\Triggers\Bitrix24EnsureUserTrigger;
use App\Triggers\Bitrix24InviteUserTrigger;
use App\Triggers\Bitrix24RemoveAdminTrigger;
use App\Triggers\Bitrix24RemoveFromDepartmentTrigger;
use App\Triggers\Bitrix24UpdateUserTrigger;
use App\Triggers\JiraAddToProjectTrigger;
use App\Triggers\JiraRemoveFromProjectTrigger;
use App\Triggers\RegruGrantEmailTrigger;
use App\Triggers\RegruRevokeEmailTrigger;
use App\Triggers\SlackInviteChannelTrigger;
use App\Triggers\SlackKickChannelTrigger;
use App\Triggers\TelegramGroupInviteTrigger;
use App\Triggers\TelegramGroupKickTrigger;
use ArcheeNic\PermissionRegistry\Models\PermissionTrigger;
use Illuminate\Database\Seeder;

class TriggerSeeder extends Seeder
{
    public function run(): void
    {
        $triggers = [
            ['name' => 'Bitrix24 Ensure User', 'class_name' => Bitrix24EnsureUserTrigger::class, 'description' => 'Ensure user exists and active in Bitrix24', 'type' => 'grant'],
            ['name' => 'Bitrix24 Add To Department', 'class_name' => Bitrix24AddToDepartmentTrigger::class, 'description' => 'Add user to Bitrix24 department', 'type' => 'grant'],
            ['name' => 'Bitrix24 Remove From Department', 'class_name' => Bitrix24RemoveFromDepartmentTrigger::class, 'description' => 'Remove user from Bitrix24 department', 'type' => 'revoke'],
            ['name' => 'Bitrix24 Invite', 'class_name' => Bitrix24InviteUserTrigger::class, 'description' => 'Invite user to Bitrix24', 'type' => 'grant'],
            ['name' => 'Bitrix24 Block', 'class_name' => Bitrix24BlockUserTrigger::class, 'description' => 'Block user in Bitrix24', 'type' => 'revoke'],
            ['name' => 'Bitrix24 Remove Admin', 'class_name' => Bitrix24RemoveAdminTrigger::class, 'description' => 'Remove admin from Bitrix24', 'type' => 'revoke'],
            ['name' => 'Bitrix24 Change Department', 'class_name' => Bitrix24ChangeDepartmentTrigger::class, 'description' => 'Change department in Bitrix24', 'type' => 'grant'],
            ['name' => 'Bitrix24 Update User', 'class_name' => Bitrix24UpdateUserTrigger::class, 'description' => 'Update user in Bitrix24', 'type' => 'grant'],
            ['name' => 'Slack Invite', 'class_name' => SlackInviteChannelTrigger::class, 'description' => 'Invite to Slack channel', 'type' => 'grant'],
            ['name' => 'Slack Kick', 'class_name' => SlackKickChannelTrigger::class, 'description' => 'Kick from Slack channel', 'type' => 'revoke'],
            ['name' => 'Jira Add', 'class_name' => JiraAddToProjectTrigger::class, 'description' => 'Add to Jira project', 'type' => 'grant'],
            ['name' => 'Jira Remove', 'class_name' => JiraRemoveFromProjectTrigger::class, 'description' => 'Remove from Jira project', 'type' => 'revoke'],
            ['name' => 'Bitbucket Add', 'class_name' => BitbucketAddToRepoTrigger::class, 'description' => 'Add to Bitbucket repo', 'type' => 'grant'],
            ['name' => 'Bitbucket Remove', 'class_name' => BitbucketRemoveFromRepoTrigger::class, 'description' => 'Remove from Bitbucket repo', 'type' => 'revoke'],
            ['name' => 'Regru Grant Email', 'class_name' => RegruGrantEmailTrigger::class, 'description' => 'Create email on Reg.ru', 'type' => 'grant'],
            ['name' => 'Regru Revoke Email', 'class_name' => RegruRevokeEmailTrigger::class, 'description' => 'Delete email on Reg.ru', 'type' => 'revoke'],
            ['name' => 'Telegram Invite', 'class_name' => TelegramGroupInviteTrigger::class, 'description' => 'Invite to Telegram group', 'type' => 'grant'],
            ['name' => 'Telegram Kick', 'class_name' => TelegramGroupKickTrigger::class, 'description' => 'Kick from Telegram group', 'type' => 'revoke'],
        ];

        foreach ($triggers as $trigger) {
            PermissionTrigger::firstOrCreate(
                ['class_name' => $trigger['class_name']],
                $trigger
            );
        }
    }
}
