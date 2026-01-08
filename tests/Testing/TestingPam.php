<?php

declare(strict_types = 1);

namespace Weiran\System\Tests\Testing;

use Throwable;
use Weiran\Framework\Exceptions\ApplicationException;
use Weiran\Framework\Helper\StrHelper;
use Weiran\System\Action\Pam;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamRole;
use Weiran\System\Models\PamRoleAccount;

/**
 * 随机获取数据
 */
class TestingPam
{
    public static function backend()
    {
        return PamAccount::passport(env('TESTING_BACKEND'));
    }

    /**
     * 获取随机用户名
     *
     * @param bool $is_register 是否已经注册
     *
     * @return mixed
     */
    public static function username(bool $is_register = true)
    {
        $Db = PamAccount::inRandomOrder();
        if ($is_register) {
            $Db->where('password', '!=', '');
        }
        else {
            $Db->where('password', '=', '');
        }

        return $Db->value('username');
    }

    /**
     * 获取随机AccountId
     *
     * @param bool $is_register 是否已经注册
     */
    public static function id(bool $is_register = true): int
    {
        $Db = PamAccount::inRandomOrder();
        if ($is_register) {
            $Db->where('password', '!=', '');
        }
        else {
            $Db->where('password', '=', '');
        }

        return $Db->value('id');
    }

    /**
     * 获取随机账号
     */
    public static function randUser(): PamAccount
    {
        return PamAccount::where('type', PamAccount::TYPE_USER)->inRandomOrder()->first();
    }

    /**
     * @throws Throwable
     * @throws ApplicationException
     */
    public static function randParentUser(): PamAccount
    {
        $query = PamAccount::where('type', PamAccount::TYPE_USER)
            ->where('parent_id', 0)
            ->inRandomOrder();
        $user  = (clone $query)->first();
        if (!$user) {
            $Pam = new Pam();
            $Pam->register(poppy_faker()->phoneNumber);
            $user = $Pam->getPam();
        }

        return $user;
    }

    /**
     * 获取随机子用户
     *
     * @param int $parentId 父级用户ID
     *
     * @throws Throwable
     * @throws ApplicationException
     */
    public static function randChildUser(int $parentId): PamAccount
    {
        $query = PamAccount::where('type', PamAccount::TYPE_USER)
            ->where('parent_id', $parentId)
            ->inRandomOrder();
        $user  = (clone $query)->first();
        if (!$user) {
            $username = PamAccount::whereKey($parentId)->value('username');
            $Pam      = new Pam();
            $Pam->setParentId($parentId);
            $Pam->register($username . ':' . poppy_faker()->phoneNumber);
            $user = $Pam->getPam();
        }
        return $user;
    }

    /**
     * 获取随机后台账号
     */
    public static function randBackend(): PamAccount
    {
        return PamAccount::where('type', PamAccount::TYPE_BACKEND)->inRandomOrder()->first();
    }

    /**
     * 随机后提用户
     */
    public static function randRoot(): PamAccount
    {
        $role = PamRole::where('name', PamRole::BE_ROOT)->value('id');
        $Db   = PamAccount::where('type', PamAccount::TYPE_BACKEND)
            ->whereIn('id', PamRoleAccount::where('role_id', $role)->pluck('account_id'))
            ->inRandomOrder();

        return $Db->first();
    }

    /**
     * 除去测试用户
     */
    public static function exclude(): array
    {
        $users = StrHelper::separate(PHP_EOL, (string) sys_setting('weiran-system::testing.users'));
        if (!$users) {
            return [];
        }

        return PamAccount::whereIn('mobile', $users)->pluck('id')->toArray();
    }
}
