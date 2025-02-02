<?php

declare(strict_types = 1);

namespace Weiran\System\Events;

class BePamLogoutEvent
{
    public int $accountId;

    /**
     * @param int $accountId
     */
    public function __construct(int $accountId)
    {
        $this->accountId = $accountId;
    }


}