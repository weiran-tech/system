<?php

declare(strict_types = 1);

namespace Weiran\System\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Weiran\Framework\Database\Eloquent\LegacySerializeData;

/**
 * User\Models\PamBin
 * @property int         $id
 * @property string      $type          类型
 * @property string      $account_type  账号类型
 * @property string      $value         值
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|PamBan newModelQuery()
 * @method static Builder|PamBan newQuery()
 * @method static Builder|PamBan query()
 * @mixin Eloquent
 */
class PamBan extends Model
{
    use LegacySerializeData;

    // 黑白名单类型
    const WB_TYPE_BLACK = 'black';
    const WB_TYPE_WHITE = 'white';

    // 封禁类型
    const TYPE_IP     = 'ip';
    const TYPE_DEVICE = 'device';

    protected $table = 'pam_ban';

    protected $fillable = [
        'account_type',
        'type',
        'value',
        'ip_start',
        'ip_end',
        'note',
    ];

    /**
     * @param null|string $key
     * @param bool        $check_key
     * @return array|string
     */
    public static function kvType($key = null, $check_key = false)
    {
        $desc = [
            self::TYPE_IP     => 'IP',
            self::TYPE_DEVICE => '设备',
        ];

        return kv($desc, $key, $check_key);
    }

    /**
     * ip是否允许登录
     * @param string $ip ip
     * @return bool
     */
    public static function ipIsAllow($ip): bool
    {
        if ($ip === 'unknown') {
            return true;
        }

        if (self::where('type', self::TYPE_IP)->where('value', $ip)->first()) {
            return false;
        }

        return true;
    }

    /**
     * 是否允许该设备登录
     * @param string $device device
     * @return bool
     */
    public static function deviceIsAllow($device): bool
    {
        if (self::where('type', self::TYPE_DEVICE)->where('value', $device)->first()) {
            return false;
        }

        return true;
    }

    /**
     * 登录是否允许
     * @param string|null $ip     ip
     * @param string|null $device 设备
     * @return bool
     */
    public static function loginIsAllow($ip = null, $device = null): bool
    {
        $allow_ip = $allow_device = true;
        if ($ip) {
            $allow_ip = self::ipIsAllow($ip);
        }
        if ($device) {
            $allow_device = self::deviceIsAllow($device);
        }
        return $allow_ip && $allow_device;
    }

    /**
     * 设备 KEY
     * @param string $type
     * @return string
     */
    public static function banDeviceIsOpen(string $type): string
    {
        $key = 'py-system::ban.type-' . $type;
        $bw  = sys_setting($key, PamBan::WB_TYPE_BLACK);
        return sys_setting('py-system::ban.device_' . $bw . '_' . $type . '_is_open', 'Y');
    }
}
