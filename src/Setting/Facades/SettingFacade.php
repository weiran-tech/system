<?php

declare(strict_types = 1);

namespace Weiran\System\Setting\Facades;

use Illuminate\Support\Facades\Facade as IlluminateFacade;
use Weiran\Core\Classes\Contracts\SettingContract;

/**
 * 设置 Facade
 */
class SettingFacade extends IlluminateFacade
{
    /**
     * 获取组件的注册名称
     * @return string
     */
    protected static function getFacadeAccessor():string
    {
        return SettingContract::class;
    }
}