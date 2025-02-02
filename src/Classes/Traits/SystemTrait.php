<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Traits;

use Weiran\Core\Classes\Contracts\SettingContract;
use Weiran\System\Setting\Repository\SettingRepository;

/**
 * Db Trait Db 工具
 */
trait SystemTrait
{

    /**
     * 检查当前是否是在事务中
     * @return SettingRepository
     */
    protected function sysSetting(): SettingRepository
    {
        return app(SettingContract::class);
    }
}