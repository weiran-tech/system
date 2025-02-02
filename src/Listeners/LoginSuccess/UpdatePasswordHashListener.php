<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\LoginSuccess;

use Illuminate\Support\Str;
use Weiran\Framework\Classes\Traits\PoppyTrait;
use Weiran\System\Events\LoginSuccessEvent;
use Weiran\System\Http\Middlewares\AuthenticateSession;

/**
 * 登录成功更新登录次数 + 最后登录时间
 */
class UpdatePasswordHashListener
{
    use PoppyTrait;

    /**
     * @param LoginSuccessEvent $event 登录成功
     */
    public function handle(LoginSuccessEvent $event): void
    {
        $name = $event->guard;
        if ($name && !Str::contains($name, 'jwt')) {
            $hashKey = AuthenticateSession::hashGuard($name);
            $this->pySession()->put($hashKey, $event->pam->getAuthPassword());
        }
    }
}

