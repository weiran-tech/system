<?php

namespace Weiran\System\Http\Request\Web\OpenApi\Auth;

use OpenApi\Attributes as OA;
use Weiran\System\Http\Request\Web\OpenApi\ResponseBaseBody;

#[OA\Schema(
    description: '登录成功',
)]
class SystemAuthAccessBody extends ResponseBaseBody
{
    #[OA\Property(
        description: '登录成功返回的token信息',
        properties: [
            new OA\Property(property: 'id', description: 'ID', type: 'integer'),
            new OA\Property(property: 'username', description: '用户名', type: 'string'),
            new OA\Property(property: 'mobile', description: '手机号', type: 'string'),
            new OA\Property(property: 'email', description: '邮箱', type: 'string'),
            new OA\Property(property: 'type', description: '类型', type: 'string'),
            new OA\Property(property: 'is_enable', description: '是否启用(Y|N)', type: 'string'),
            new OA\Property(property: 'disable_reason', description: '禁用原因', type: 'string'),
            new OA\Property(property: 'created_at', description: '创建时间', type: 'string'),
            new OA\Property(property: 'updated_at', description: '更新时间', type: 'string')
        ],
        type: 'object'
    )]
    public object $data;
}