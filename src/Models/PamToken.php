<?php

declare(strict_types = 1);

namespace Weiran\System\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Weiran\Framework\Database\Eloquent\LegacySerializeData;

/**
 * 账号 token
 * @property int         $id
 * @property int         $account_id   用户id
 * @property string      $device_id    设备id
 * @property string      $device_type  设备类型
 * @property string      $login_ip     token 登录IP
 * @property string      $token_hash   token 的md5值
 * @property string      $expired_at   过期时间
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|PamToken newModelQuery()
 * @method static Builder|PamToken newQuery()
 * @method static Builder|PamToken query()
 * @mixin Eloquent
 */
class PamToken extends Model
{
    use LegacySerializeData;

    protected $table = 'pam_token';

    protected $fillable = [
        'account_id',
        'device_id',
        'device_type',
        'login_ip',
        'token_hash',
        'expired_at',
    ];
}
