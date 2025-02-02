<?php

declare(strict_types = 1);

namespace Weiran\System\Models\Policies;

use Weiran\System\Classes\Traits\PolicyTrait;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamRole;
use Weiran\System\Models\SysConfig;

/**
 * PamAccount 策略
 */
class PamAccountPolicy
{

    use PolicyTrait;


    protected static array $permissionMap = [
        'password' => 'backend:py-system.pam.password',
    ];

    /**
     * 编辑
     * @param PamAccount $pam 账号
     * @return bool
     */
    public function create(PamAccount $pam)
    {
        return true;
    }

    /**
     * 编辑
     * @param PamAccount $pam  账号
     * @param PamAccount $item 账号
     * @return bool
     */
    public function edit(PamAccount $pam, PamAccount $item)
    {
        return true;
    }

    /**
     * 保存权限
     * @param PamAccount $pam  账号
     * @param PamAccount $item 账号
     * @return bool
     */
    public function enable(PamAccount $pam, PamAccount $item)
    {
        return $item->is_enable === SysConfig::NO;
    }


    public function password(PamAccount $pam, PamAccount $item): bool
    {
        return true;
    }


    /**
     * 删除
     * @param PamAccount $pam  账号
     * @param PamAccount $item 账号
     * @return bool
     */
    public function disable(PamAccount $pam, PamAccount $item)
    {
        // 不得禁用自身
        if ($pam->id === $item->id) {
            return false;
        }

        return !$this->enable($pam, $item);
    }

    //region 后台用户权限

    /**
     * 设置后台用户通行证
     * @param PamAccount $pam
     * @param PamAccount $item
     * @return bool
     */
    public function beMobile(PamAccount $pam, PamAccount $item): bool
    {
        return $pam->hasRole(PamRole::BE_ROOT) &&
            $item->type === PamAccount::TYPE_BACKEND;
    }


    public function beClearMobile(PamAccount $pam, PamAccount $item): bool
    {
        return $pam->hasRole(PamRole::BE_ROOT) &&
            $item->type === PamAccount::TYPE_BACKEND &&
            strlen($item->mobile) === 17;   // 33023-{11};
    }

    //endregion
}