<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

use Weiran\System\Models\PamAccount;

/**
 * 用户注册事件
 */
class PamRegisteredEvent
{
    public PamAccount $pam;

    /**
     * PamRegisteredEvent constructor.
     */
    public function __construct(PamAccount $pam)
    {
        $this->pam = $pam;
    }
}
