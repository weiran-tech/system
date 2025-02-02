<?php

declare(strict_types = 1);

namespace Weiran\System\Listeners\PermissionInit;

use Exception;
use Weiran\Core\Events\PermissionInitEvent;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamPermission;
use Weiran\System\Models\PamRole;

/**
 * 记录登录日志
 */
class InitToDbListener
{
    /**
     * @param PermissionInitEvent $event 登录成功
     * @throws Exception
     */
    public function handle(PermissionInitEvent $event)
    {
        $permissions = $event->permissions;
        // 删除多余权限
        PamPermission::whereNotIn('name', $permissions->keys())->delete();

        // insert db
        foreach ($permissions as $key => $permission) {
            PamPermission::updateOrCreate([
                'name' => $key,
            ], [
                'title'       => $permission->description(),
                'type'        => $permission->type(),
                'group'       => $permission->group(),
                'module'      => $permission->module(),
                'root'        => $permission->root(),
                'description' => '',
            ]);
        }

        // 所有后台权限都赋值给　root　用户组
        // 然后清空角色缓存表，　使角色定义生效
        $permissions = PamPermission::where('type', PamAccount::TYPE_BACKEND)->get();

        /** @var PamRole $role */
        $role = PamRole::where('name', PamRole::BE_ROOT)->first();
        $role->syncPermission($permissions);
    }
}

