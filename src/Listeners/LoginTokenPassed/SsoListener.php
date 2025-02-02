<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\LoginTokenPassed;

use Exception;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\System\Action\Sso;
use Weiran\System\Events\LoginTokenPassedEvent;

/*
|--------------------------------------------------------------------------
| 单点登录监听
|--------------------------------------------------------------------------
| 单点登录必须传递 DeviceId/DeviceType
| HeaderOfDeviceId   : X-ID (device_id)
| HeaderOfDeviceTYPE : X-OS (device_type)
*/

class SsoListener
{
    /**
     * Handle the event.
     * @param LoginTokenPassedEvent $event 用户账号
     * @return void
     * @throws ApplicationException |Exception
     */
    public function handle(LoginTokenPassedEvent $event): void
    {
        $Sso = new Sso();
        if (!$Sso->handle($event->pam, $event->deviceId, $event->deviceType, $event->token)) {
            throw new ApplicationException($Sso->getError());
        }
    }
}
