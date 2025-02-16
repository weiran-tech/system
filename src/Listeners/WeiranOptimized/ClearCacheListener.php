<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\WeiranOptimized;

use Weiran\Framework\Events\WeiranOptimized;

/**
 * 清除缓存
 */
class ClearCacheListener
{

    /**
     * @param WeiranOptimized $event 框架优化
     */
    public function handle(WeiranOptimized $event)
    {
        // 清空所有缓存的设置项目
        sys_tag('weiran-system')->clear();
    }
}

