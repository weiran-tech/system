<?php

declare(strict_types = 1);

namespace Weiran\System\Action;

use Exception;
use Illuminate\Support\Arr;
use Weiran\Core\Classes\Traits\CoreTrait;
use Weiran\Framework\Classes\Traits\AppTrait;
use Weiran\System\Classes\Traits\PamTrait;
use Weiran\System\Events\RolePermissionUpdatedEvent;
use Weiran\System\Models\PamPermission;
use Weiran\System\Models\PamRole;
use Weiran\System\Models\PamRoleAccount;

/**
 * 角色action
 */
class Role
{
    use AppTrait, PamTrait, CoreTrait;

    /**
     * @var PamRole
     */
    protected PamRole $role;

    /**
     * 创建需求
     * @param array    $data 创建数据
     * @param null|int $id   角色id
     * @return bool
     */
    public function establish(array $data, int $id = null): bool
    {
        $initDb = [
            'title'       => (string) Arr::get($data, 'title', ''),
            'type'        => (string) Arr::get($data, 'type', ''),
            'description' => (string) Arr::get($data, 'description', ''),
        ];

        // init
        $id && $this->init($id);

        if ($id) {
            // 编辑时候类型和名称不允许编辑
            unset($initDb['type']);
            $this->role->update($initDb);
        }
        else {
            $this->role = PamRole::create($initDb);
        }

        return true;
    }

    /**
     * 保存权限
     * @param array $permission_ids 所有的权限列表
     * @param int   $role_id        角色ID
     * @return bool
     */
    public function savePermission(int $role_id, array $permission_ids): bool
    {
        if (!$this->checkPam()) {
            return false;
        }

        $this->init($role_id);

        if ($this->pam->can('savePermission', PamRole::class)) {
            return $this->setError(trans('weiran-system::action.role.no_policy_to_save_permission'));
        }

        if ($permission_ids) {
            $objPermissions = PamPermission::whereIn('id', $permission_ids)->get();
            if (!$objPermissions->count()) {
                return $this->setError(trans('weiran-system::action.role.permission_error'));
            }
            $this->role->syncPermission($objPermissions);
        }
        else {
            $this->role->syncPermission([]);
        }

        $this->role->flushPermissionRole();

        event(new RolePermissionUpdatedEvent($this->role));

        return true;
    }

    /**
     * @param int $id 角色id
     */
    public function init(int $id): void
    {
        $this->role = PamRole::findOrFail($id);
    }

    public function getRole(): PamRole
    {
        return $this->role;
    }

    /**
     * 获取所有权限以及默认值
     * @param int  $id      角色id
     * @param bool $has_key 是否有值
     * @return array|bool
     */
    public function permissions(int $id, bool $has_key = true)
    {
        $role = PamRole::find($id);
        if (!$role) {
            return $this->setError('角色不存在');
        }
        $permissions = $this->corePermission()->permissions();
        $type        = $role->type;

        // 权限映射
        if ($map = config('poppy.system.role_type_map')) {
            $type = $map[$type] ?? $type;
        }

        $keys              = $permissions->keys();
        $match             = PamPermission::where('type', $type)->whereIn('name', $keys)->pluck('id', 'name');
        $collectPermission = collect();
        foreach ($permissions as $key => $permission) {
            $tmp = $permission->toArray();
            $id  = $match->get($tmp['key']);
            // 去掉本用户组不可控制的权限
            if (!$id) {
                continue;
            }
            $tmp['id'] = $match->get($tmp['key']);
            $collectPermission->put($key, $tmp);
        }

        $permission = [];
        $collectPermission->each(function ($item, $key) use (&$permission, $role) {
            $root    = [
                'title'  => $item['root_title'],
                'root'   => $item['root'],
                'groups' => [],
            ];
            $rootKey = $item['root'];
            if (!isset($permission[$rootKey])) {
                $permission[$rootKey] = $root;
            }
            $groupKey = $item['group'];
            $group    = [
                'group'       => $item['group'],
                'title'       => $item['group_title'],
                'permissions' => [],
            ];
            if (!isset($permission[$rootKey]['groups'][$groupKey])) {
                $permission[$rootKey]['groups'][$groupKey] = $group;
            }

            $item['value'] = (int) $role->hasPermission($key);

            unset(
                $item['is_default'],
                $item['root'],
                $item['group'],
                $item['module'],
                $item['root_title'],
                $item['type'],
                $item['group_title']
            );

            $permission[$rootKey]['groups'][$groupKey]['permissions'][] = $item;
        });

        if (!$has_key) {
            $p = [];
            foreach ($permission as $sp) {
                $pg = $sp;
                unset($pg['groups']);
                foreach ($sp['groups'] as $spg) {
                    $pg['groups'][] = $spg;
                }
                $p[] = $pg;
            }
            $permission = $p;
        }

        return $permission;
    }

    /**
     * 删除数据
     * @param int $id 角色id
     * @return bool
     * @throws Exception
     */
    public function delete(int $id): bool
    {
        if (!$this->checkPam()) {
            return false;
        }

        $id && $this->init($id);

        if (!$this->pam->can('delete', $this->role)) {
            return $this->setError(trans('weiran-system::action.role.no_policy_to_delete'));
        }

        if (PamRoleAccount::where('role_id', $id)->exists()) {
            return $this->setError(trans('weiran-system::action.role.role_has_account'));
        }

        // 删除权限
        $this->role->syncPermission([]);
        // 删除角色
        $this->role->delete();
        return true;
    }
}
