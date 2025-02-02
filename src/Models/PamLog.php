<?php

declare(strict_types = 1);

namespace Weiran\System\Models;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Weiran\Framework\Database\Eloquent\LegacySerializeData;

/**
 * 登录日志
 * @property int             $id           ID
 * @property int             $account_id   账户ID
 * @property int             $parent_id    父账号ID
 * @property string          $account_type 账户类型
 * @property string          $type         登录日志类型, success, error, warning
 * @property string          $ip           IP
 * @property string          $area_text    地区方式
 * @property string          $area_name    地区名字
 * @property string          $note         备注
 * @property Carbon          $created_at   创建时间
 * @property Carbon          $updated_at   修改时间
 * @property-read PamAccount $pam
 * @mixin Eloquent
 */
class PamLog extends Model
{
    use LegacySerializeData;

    protected $table = 'pam_log';

    protected $fillable = [
        'account_id',
        'parent_id',
        'account_type',
        'type',
        'ip',
        'note',
        'area_text',   // 山东济南联通
        'area_name',   // 济南
    ];

    /**
     * 链接用户表
     * @return BelongsTo
     */
    public function pam()
    {
        return $this->belongsTo(PamAccount::class, 'account_id', 'id');
    }
}