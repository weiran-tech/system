<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\TokenRenew;

use Exception;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Action\Sso;
use Weiran\System\Events\TokenRenewEvent;

class TokenRenewListener
{
    /**
     * Handle the event.
     * @param TokenRenewEvent $event 用户账号
     * @return void
     * @throws ApplicationException |Exception
     */
    public function handle(TokenRenewEvent $event): void
    {
        $Sso = new Sso();
        if (!$Sso->renew($event->pam, $event->deviceId, $event->deviceType, $event->token)) {
            throw new ApplicationException($Sso->getError());
        }
    }
}
