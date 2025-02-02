<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\PoppyOptimized;

use Weiran\Framework\Events\PoppyOptimized;
use Weiran\System\Action\Ban;

/**
 * 系统初始化
 */
class SystemInitListener
{

    /**
     * @param PoppyOptimized $event 框架优化
     */
    public function handle(PoppyOptimized $event): void
    {
        // init ban
        (new Ban())->initCache();
    }
}

