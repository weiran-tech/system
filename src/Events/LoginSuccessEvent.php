<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

use Weiran\System\Models\PamAccount;

/**
 * 登录成功事件
 */
class LoginSuccessEvent
{
    /**
     * @var PamAccount 用户账户
     */
    public PamAccount $pam;

    /**
     * @var string
     */
    public string $guard;

    /**
     * 来源
     * @var string
     */
    public string $type;

    public function __construct(PamAccount $pam, string $guard, string $type = '')
    {
        $this->pam   = $pam;
        $this->guard = $guard;
        $this->type  = $type === '' ? 'login' : $type;
    }
}