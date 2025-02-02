<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\QueryExecuted;

use Illuminate\Database\Events\QueryExecuted;
use Weiran\System\Action\DbOptimize;

/**
 * 记录日志
 */
class LogListener
{
    /**
     * @param QueryExecuted $event 查询
     */
    public function handle(QueryExecuted $event): void
    {
        (new DbOptimize())->log($event);
    }
}