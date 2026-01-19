<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\WeiranOptimized;

use Weiran\Framework\Events\WeiranOptimizedEvent;

/**
 * 清除缓存
 */
class ClearCacheListener
{
    /**
     * @param WeiranOptimizedEvent $event 框架优化
     */
    public function handle(WeiranOptimizedEvent $event)
    {
        // 清空所有缓存的设置项目
        sys_tag('weiran-system')->clear();
    }
}
