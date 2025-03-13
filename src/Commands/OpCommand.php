<?php

declare(strict_types = 1);

namespace Weiran\System\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use JsonException;

class OpCommand extends Command
{
    protected $signature = 'weiran:system:op
        {action : Operation Type}
    ';

    protected $description = 'Operation for system';

    /**
     * @throws JsonException
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        switch ($action) {
            case 'gen-secret':
                $secret = md5(microtime(true) . Str::random());
                $this->writeNewEnvironmentFileWith($secret);
                $this->info(sys_gen_mk('system.op', '生成替换并汇报成功'));
                break;
            case 'secret':
                $this->info(sys_gen_mk('system.op', '当前的密钥为:' . config('weiran.system.secret')));
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
            'WEIRAN_SECRET=' . $key,
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
        $escaped = preg_quote('=' . $this->laravel['config']['weiran.system.secret'], '/');
        return "/^WEIRAN_SECRET{$escaped}/m";
    }
}