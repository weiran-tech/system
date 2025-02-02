<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\PoppyOptimized;

use Weiran\Framework\Events\PoppyOptimized;

/**
 * 清除缓存
 */
class ClearCacheListener
{

    /**
     * @param PoppyOptimized $event 框架优化
     */
    public function handle(PoppyOptimized $event)
    {
        // 清空所有缓存的设置项目
        sys_tag('py-system')->clear();
    }
}

