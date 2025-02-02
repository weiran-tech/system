<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Progress;

use Weiran\System\Classes\Contracts\ProgressContract;
use Weiran\System\Classes\Traits\FixTrait;

/**
 * 数据库更新读取
 */
abstract class BaseProgress implements ProgressContract
{

    use FixTrait;


    public function __construct()
    {
        $this->fixInit();
    }
}
