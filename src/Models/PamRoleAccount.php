<?php

declare(strict_types = 1);

namespace Weiran\System\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Weiran\Framework\Database\Eloquent\LegacySerializeData;

/**
 * 用户角色映射
 * @property int $account_id 账户id
 * @property int $role_id    角色id
 * @mixin Eloquent
 */
class PamRoleAccount extends Model
{
    use LegacySerializeData;

    protected $table = 'pam_role_account';

    protected $primaryKey = 'account_id';

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'role_id',
    ];

    /**
     * 通过账户id 获取角色id, 加入角色ID 缓存
     * @param int $account_id 用户id
     * @return mixed
     */
    public static function getRoleIdByAccountId($account_id)
    {
        static $rel = [];
        if (!isset($rel[$account_id])) {
            $rel[$account_id] = self::where('account_id', $account_id)->value('role_id');
        }

        return $rel[$account_id];
    }
}