<?php

return [
    'captcha'   => [
        'send_success' => '发送验证码成功',
    ],
    'auth'      => [
        'throttle' => '请求频繁, 请 :seconds 秒后重试',
    ],
    'classes'   => [
        'models' => [
            'pam_account'         => '用户账户',
            'pam_role'            => '用户角色',
            'pam_ban'             => '用户封禁',
            'pam_log'             => '登录日志',
            'pam_permission'      => '用户权限',
            'pam_permission_role' => '权限角色',
            'pam_role_account'    => '账户角色',
            'pam_token'           => '登录凭证',
            'sys_config'          => '系统设置',
        ],
    ],
    'exception' => [
        'setting_key_not_match'      => '设置给定的键 :key 格式不匹配',
        'setting_value_out_of_range' => '设置给定的键 :key 设定内容超长'
    ],
    'policy'    => [
        'pam_role'    => [
            'create'     => '角色创建',
            'edit'       => '角色编辑',
            'permission' => '角色权限',
            'delete'     => '角色删除',
        ],
        'pam_account' => [
            'create'        => '账号创建',
            'edit'          => '账号编辑',
            'enable'        => '账号启用',
            'disable'       => '账号禁用',
            'beMobile'      => '设置后台手机号',
            'beClearMobile' => '清空后台手机号',
        ],
    ],
];