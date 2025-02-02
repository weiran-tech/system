<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\PamPasswordModified;

use Exception;
use Weiran\System\Action\Sso;
use Weiran\System\Events\PamPasswordModifiedEvent;
use Weiran\System\Models\PamAccount;

/**
 * 修改密码开启 SSO 登录的监听
 */
class SsoListener
{
    /**
     * @throws Exception
     */
    public function handle(PamPasswordModifiedEvent $event): void
    {
        if ($event->pam->type === PamAccount::TYPE_BACKEND) {
            return;
        }
        (new Sso())->banUser($event->pam->id);
    }
}
