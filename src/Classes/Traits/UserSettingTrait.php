<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Traits;


/**
 * 用户设置和获取
 */
trait UserSettingTrait
{

    /**
     * 根据 用户ID获取配置
     * @param integer $account_id
     * @param string  $key
     * @return array|mixed
     */
    public function userSettingGet(int $account_id, string $key)
    {
        return sys_setting('user::pam-' . $account_id . '.' . $key) ?: [];
    }


    /**
     * @param int    $account_id
     * @param string $key       key
     * @param array  $values    值
     * @param array  $available 数组
     * @return bool
     */
    public function userSettingSet(int $account_id, string $key, array $values, array $available = []): bool
    {
        $data = [];
        foreach ($values as $k => $v) {
            if ($available && !in_array($k, $available, true)) {
                continue;
            }

            $data[$k] = $v;
        }

        if (!app('poppy.system.setting')->set([
            'user::pam-' . $account_id . '.' . $key => $data,
        ])) {
            return $this->setError(app('poppy.system.setting')->getError());
        }
        return true;
    }
}