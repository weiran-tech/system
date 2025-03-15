<?php

declare(strict_types = 1);

namespace Weiran\System\Commands;

use Illuminate\Console\Command;
use Weiran\System\Models\PamRole;

/**
 * 项目初始化
 */
class InstallCommand extends Command
{
    /**
     * 前端部署.
     * @var string
     */
    protected $signature = 'system:install';

    /**
     * 描述
     * @var string
     */
    protected $description = 'Install system module.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // check
        if (PamRole::where('name', PamRole::BE_ROOT)->exists()) {
            $this->warn('You Already Installed!');

            return;
        }

        $this->line('Start Install Weiran Framework!');

        /* Role
         -------------------------------------------- */
        $this->warn('Init UserRole Ing...');
        $this->call('system:user', [
            'do' => 'init_role',
        ]);
        $this->info('Install User Roles Success');

        /* permission
         -------------------------------------------- */
        $this->warn('Init Rbac Permission...');
        $this->call('weiran:core:permission', [
            'do' => 'init',
        ]);
        $this->info('Init Rbac Permission Success');
    }
}