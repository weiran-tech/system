<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Testing;

use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamRole;

/**
 * 随机获取数据
 */
class TestingRole
{

    /**
     * 获取随机角色
     * @return PamRole
     */
    public static function randUser(): PamRole
    {
        $Db = PamRole::where('type', PamAccount::TYPE_USER)->inRandomOrder();
        return $Db->first();
    }

    /**
     * 获取随机后台账号
     * @return PamRole
     */
    public static function randBackend(): PamRole
    {
        $Db = PamRole::where('type', PamAccount::TYPE_BACKEND)->inRandomOrder();
        return $Db->first();
    }
}
