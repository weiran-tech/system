<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\WeiranOptimized;

use Weiran\Framework\Events\WeiranOptimized;
use Weiran\System\Action\Ban;

/**
 * 系统初始化
 */
class SystemInitListener
{

    /**
     * @param WeiranOptimized $event 框架优化
     */
    public function handle(WeiranOptimized $event): void
    {
        // init ban
        (new Ban())->initCache();
    }
}

