<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Traits;

use Weiran\System\Models\PamAccount;

/**
 * 策略映射
 */
trait PolicyTrait
{

    /**
     * 在 XX 条件的前置, 用于进行权限的前置限制
     * @param PamAccount $pam     账号
     * @param string     $ability 能力
     * @return bool|null
     */
    public function before(PamAccount $pam, string $ability): ?bool
    {
        if (!property_exists($this, 'permissionMap')) {
            return null;
        }
        $permission = self::$permissionMap[$ability] ?? '';
        if ($permission) {
            return $pam->capable($permission) ? null : false;
        }
        return null;
    }

    /**
     * 策略映射, 此策略映射的目的是为了和控制器共享定义, 但是为了解耦操作
     * 建议拆分权限定义和策略定义
     * @return mixed
     */
    public static function getPermissionMap(): array
    {
        return self::$permissionMap;
    }
}