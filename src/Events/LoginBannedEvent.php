<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

use Weiran\System\Models\PamAccount;

/**
 * 登录受限事件
 * 用于登录过程中拦截用户/设备等信息
 */
class LoginBannedEvent
{
    /**
     * @var PamAccount 用户账户
     */
    public PamAccount $pam;

    /**
     * @var string
     */
    public string $guard;

    public function __construct(PamAccount $pam, string $guard)
    {
        $this->pam   = $pam;
        $this->guard = $guard;
    }
}