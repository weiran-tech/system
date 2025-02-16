<?php

namespace Weiran\System\Http\Request\Web\OA\Auth;

use OpenApi\Attributes as OAT;
use Weiran\System\Http\Request\Web\OA\ResponseBaseBody;

#[OAT\Schema(
    description: '登录成功',
)]
class SystemAuthAccessBody extends ResponseBaseBody
{
    #[OAT\Property(
        description: '登录成功返回的token信息',
        properties: [
            new OAT\Property(property: 'id', description: 'ID', type: 'integer'),
            new OAT\Property(property: 'username', description: '用户名', type: 'string'),
            new OAT\Property(property: 'mobile', description: '手机号', type: 'string'),
            new OAT\Property(property: 'email', description: '邮箱', type: 'string'),
            new OAT\Property(property: 'type', description: '类型', type: 'string'),
            new OAT\Property(property: 'is_enable', description: '是否启用(Y|N)', type: 'string'),
            new OAT\Property(property: 'disable_reason', description: '禁用原因', type: 'string'),
            new OAT\Property(property: 'created_at', description: '创建时间', type: 'string'),
            new OAT\Property(property: 'updated_at', description: '更新时间', type: 'string')
        ],
        type: 'object'
    )]
    public object $data;
}