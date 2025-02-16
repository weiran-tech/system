<?php

declare(strict_types = 1);

namespace Weiran\System\Commands;

use Illuminate\Console\Command;
use Weiran\System\Action\Ban;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamBan;

class BanCommand extends Command
{
    protected $signature = 'weiran:system:ban
        {type : account type}
        {value : ip/device}
        {--note= : note}
    ';

    protected $description = 'Ban user ip or device';

    public function handle(): int
    {
        $accountType = (string) $this->argument('type');
        $value       = trim((string) $this->argument('value'));
        $note        = (string) $this->option('note');

        if (!in_array($accountType, [
            PamAccount::TYPE_USER,
            PamAccount::TYPE_BACKEND,
        ], true)) {
            $this->error('Account Type 类型错误');
            return 1;
        }

        if (strlen($value) < 10) {
            $this->error('请输入正确的设备信息(IP/设备信息)');
            return 1;
        }

        $Ban = new Ban();

        $type = PamBan::TYPE_DEVICE;
        if ($Ban->parseIpRange($value)) {
            $type = PamBan::TYPE_IP;
        }

        $data = [
            'account_type' => $accountType,
            'type'         => $type,
            'value'        => $value,
            'note'         => $note,
        ];

        if (!$Ban->establish($data)) {
            $this->error($Ban->getError()->getMessage());
            return 1;
        }

        $this->info('添加成功');
        return 0;
    }
}