<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\PamLogout;

use Throwable;
use Weiran\System\Action\Sso;
use Weiran\System\Events\PamLogoutEvent;
use Weiran\System\Models\PamAccount;

/**
 * 前台用户触发 SSO 退出
 */
class SsoListener
{
    /**
     * @throws Throwable
     */
    public function handle(PamLogoutEvent $event): void
    {
        if ($event->pam->type === PamAccount::TYPE_BACKEND) {
            return;
        }
        $token = jwt_token();
        if ($token) {
            (new Sso())->logout($event->pam->id, $token);
        }
    }
}
