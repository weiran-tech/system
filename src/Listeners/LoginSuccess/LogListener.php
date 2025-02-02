<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\LoginSuccess;

use Weiran\Framework\Helper\EnvHelper;
use Weiran\System\Events\LoginSuccessEvent;
use Weiran\System\Models\PamLog;

/**
 * 记录登录日志
 */
class LogListener
{
    /**
     * @param LoginSuccessEvent $event 登录成功
     */
    public function handle(LoginSuccessEvent $event): void
    {
        $pam = $event->pam;

        $ip = EnvHelper::ip();

        $areaText = '';
        if (app()->bound('poppy.ext.ip_store')) {
            $areaText = app('poppy.ext.ip_store')->area($ip);
            if (is_array($areaText)) {
                $areaText = implode(' ', $areaText);
            }
        }

        PamLog::create([
            'account_id'   => $pam->id,
            'account_type' => $pam->type,
            'type'         => $event->type,
            'parent_id'    => $pam->parent_id,
            'ip'           => $ip,
            'area_text'    => $areaText,
            'area_name'    => '',
        ]);
    }
}

