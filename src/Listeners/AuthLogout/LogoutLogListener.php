<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\AuthLogout;

use Weiran\Framework\Helper\EnvHelper;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamLog;

/**
 * 退出系统Listener
 */
class LogoutLogListener
{
    /**
     * Handle the event.
     * @param PamAccount $user 用户账号
     * @return void
     */
    public function handle($user)
    {
        PamLog::create([
            'account_id'   => $user->id,
            'account_name' => $user->username,
            'account_type' => $user->type,
            'log_type'     => 'success',
            'log_ip'       => EnvHelper::ip(),
            'log_content'  => '登出系统',
        ]);
    }
}
