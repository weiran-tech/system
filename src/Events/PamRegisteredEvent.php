<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

use Weiran\System\Models\PamAccount;

/**
 * 用户注册事件
 */
class PamRegisteredEvent
{
    /**
     * @var PamAccount
     */
    public PamAccount $pam;

    /**
     * PamRegisteredEvent constructor.
     * @param PamAccount $pam
     */
    public function __construct(PamAccount $pam)
    {
        $this->pam = $pam;
    }
}