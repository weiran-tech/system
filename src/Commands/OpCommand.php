<?php

declare(strict_types = 1);

namespace Weiran\System\Commands;

use Illuminate\Console\Command;
use Weiran\System\Action\Console;

class OpCommand extends Command
{
    protected $signature = 'py-system:op
        {action : Operation Type}
    ';

    protected $description = 'Operation for system';

    public function handle(): int
    {
        $action = $this->argument('action');
        switch ($action) {
            case 'gen-secret':
                $Console = new Console();
                if ($Console->generateSecret()) {
                    $this->writeNewEnvironmentFileWith($Console->secret());
                    $this->info(sys_gen_mk('system.op', '生成替换并汇报成功'));
                }
                else {
                    $this->warn(sys_gen_mk('system.op', $Console->getError()));
                }
                break;
            case 'secret':
                $Console = new Console();
                $this->info(sys_gen_mk('system.op', '当前的密钥为:' . $Console->secret()));
                break;
            default:
                $this->warn(sys_gen_mk('system.op', '错误的 action'));
                break;
        }
        return 0;
    }


    /**
     * Write a new environment file with the given key.
     *
     * @param string $key
     * @return void
     */
    protected function writeNewEnvironmentFileWith(string $key): void
    {
        file_put_contents($this->laravel->environmentFilePath(), preg_replace(
            $this->keyReplacementPattern(),
            'PY_SECRET=' . $key,
            file_get_contents($this->laravel->environmentFilePath())
        ));
    }

    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern(): string
    {
        $escaped = preg_quote('=' . $this->laravel['config']['poppy.system.secret'], '/');
        return "/^PY_SECRET{$escaped}/m";
    }
}