<?php

declare(strict_types = 1);

namespace Weiran\System\Commands;

use Illuminate\Console\Command;
use Weiran\System\Action\Ban;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamBan;

class BanCommand extends Command
{
    protected $signature = 'system:ban
        {type : account type}
        {value : ip/device}
        {--note= : note}
    ';

    protected $description = 'Ban user ip or device';

    public function handle(): void
    {
        $type  = (string) $this->argument('type');
        $value = trim((string) $this->argument('value'));
        $note  = (string) $this->option('note');

        if (!PamAccount::kvType($type, true)) {
            $this->error('Account Type 类型错误');
        }

        if (strlen($value) < 10) {
            $this->error('请输入正确的设备信息(IP/设备信息)');
        }

        $Ban = new Ban();

        $banType = PamBan::TYPE_DEVICE;
        if ($Ban->parseIpRange($value)) {
            $banType = PamBan::TYPE_IP;
        }

        $data = [
            'account_type' => $type,
            'type'         => $banType,
            'value'        => $value,
            'note'         => $note,
        ];

        if (!$Ban->establish($data)) {
            $this->error($Ban->getError()->getMessage());
        }

        $this->info('添加成功');
    }
}