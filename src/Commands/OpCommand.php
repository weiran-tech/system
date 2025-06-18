<?php

declare(strict_types = 1);

namespace Weiran\System\Commands;

use Illuminate\Console\Command;
use JsonException;

class OpCommand extends Command
{
    protected $signature = 'system:op
        {action : Operation Type}
        {--secret=}
    ';

    protected $description = 'Operation for system';

    /**
     * @throws JsonException
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        switch ($action) {
            case 'set-secret':
                $secret = (string) $this->option('secret');
                if (strlen($secret) !== 32) {
                    $this->warn(sys_gen_mk('system.op', '密钥 [--secret] 长度必须是 32 位长度'));
                    return 0;
                }
                $this->writeNewEnvironmentFileWith($secret);
                $this->info(sys_gen_mk('system.op', '替换成功'));
                break;
            case 'show-secret':
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