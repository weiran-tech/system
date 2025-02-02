<?php

declare(strict_types = 1);

namespace Weiran\System\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Weiran\Core\Rbac\Contracts\RbacRoleContract;
use Weiran\Core\Rbac\Traits\RbacRoleTrait;
use Weiran\Framework\Database\Eloquent\LegacySerializeData;

/**
 * 用户角色
 * @property int                             $id          ID
 * @property string                          $name        标识
 * @property string                          $title       角色名称
 * @property string                          $description 描述
 * @property string                          $type        角色组
 * @property bool                            $is_system   是否系统
 * @property int                             $is_enable   是否可用
 * @property-read Collection|PamPermission[] $perms
 * @property-read Collection|PamAccount[]    $users
 * @mixin Eloquent
 */
class PamRole extends Model implements RbacRoleContract
{
    use RbacRoleTrait, LegacySerializeData;

    public const BE_ROOT = 'root';      // admin user
    public const FE_USER = 'user';      // web user

    public $timestamps = false;

    protected $table = 'pam_role';

    protected $fillable = [
        'name',
        'title',
        'description',
        'type',
        'is_system',
    ];

    /**
     * 通过角色来获取账户类型, 由于角色在单条处理中不会存在变化, 故而可以进行静态缓存
     * @param int $role_id 角色id
     * @return mixed
     */
    public static function getAccountTypeByRoleId($role_id)
    {
        static $_cache;
        if (!isset($_cache[$role_id])) {
            $_cache[$role_id] = self::where('role_id', $role_id)->value('account_type');
        }

        return $_cache[$role_id];
    }

    /**
     * 返回一维的角色对应
     * @param null|string $type 类型
     * @param string      $key  key
     * @return Collection
     */
    public static function getLinear($type = null, $key = 'id'): Collection
    {
        return self::where('type', $type)->pluck('title', $key);
    }

    /**
     * 根据账户类型获取角色
     * @param string|null $accountType 账户类型
     * @param bool        $cache       是否缓存
     * @return array
     */
    public static function getAll($accountType = null, $cache = true)
    {
        static $roles = null;
        if (empty($roles) || !$cache) {
            if ($accountType) {
                $items = self::where('account_type', $accountType)->get();
            }
            else {
                $items = self::all();
            }
            $roles = $items->pluck('id')->toArray();
        }

        return $roles;
    }

    /**
     * 获取角色信息
     * @param int  $id    角色id
     * @param null $key   key
     * @param bool $cache 是否缓存
     * @return null
     */
    public static function info($id, $key = null, $cache = true)
    {
        $roles = self::getAll(null, $cache);

        return $key
            ? $roles[$id][$key] ?? null
            : $roles[$id];
    }
}