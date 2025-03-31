<?php

declare(strict_types = 1);

namespace Weiran\System\Models;

use Carbon\Carbon;
use DB;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Weiran\Framework\Classes\Traits\KeyParserTrait;
use Weiran\Framework\Database\Eloquent\LegacySerializeData;

/**
 * 系统设置
 * @property int    $id          配置id
 * @property string $namespace   命名空间
 * @property string $group       配置分组
 * @property string $item        配置名称
 * @property string $value       配置值
 * @property string $description 配置介绍
 * @method static Builder|SysConfig applyKey($key)
 * @mixin Eloquent
 */
class SysConfig extends Model
{
    use KeyParserTrait, LegacySerializeData;

    public const STR_YES = 'Y';
    public const STR_NO  = 'N';

    // 数据库使用 0/1 来代表关/开
    public const YES = 1;
    public const NO  = 0;

    // 启用停用标识使用 enable/disable 来进行标识
    public const ENABLE  = 1;
    public const DISABLE = 0;

    // 时间定义, 时间使用 WeiranCoreDef 类替换
    public const MIN_DEBUG     = 0;
    public const MIN_ONE_HOUR  = 60;
    public const MIN_SIX_HOUR  = 360;
    public const MIN_HALF_DAY  = 720;
    public const MIN_ONE_DAY   = 1440;
    public const MIN_HALF_WEEK = 5040;
    public const MIN_ONE_WEEK  = 10080;
    public const MIN_ONE_MONTH = 43200;

    public $timestamps = false;

    protected $table = 'sys_config';

    protected $fillable = [
        'namespace',
        'group',
        'item',
        'value',
        'description',
    ];

    /**
     * Scope to find a setting record for the specified module (or plugin) name and setting name.
     * @param Builder $query query
     * @param string  $key   Specifies the setting key value, for example 'system:updates.check'
     * @return Builder
     */
    public function scopeApplyKey($query, $key)
    {
        [$namespace, $group, $item] = $this->parseKey($key);

        $query->where('namespace', $namespace)
            ->where('group', $group)
            ->where('item', $item);

        return $query;
    }

    /**
     * @param null $key key
     * @return array|string
     */
    public static function kvYn($key = null)
    {
        $desc = [
            self::NO  => '否',
            self::YES => '是',
        ];

        return kv($desc, $key);
    }

    /**
     * 字符来标识 YN
     * @param null $key
     * @return array|bool|string
     */
    public static function kvStrYn($key = null)
    {
        $desc = [
            self::STR_NO  => '否',
            self::STR_YES => '是',
        ];

        return kv($desc, $key);
    }

    /**
     * 禁用/启用
     * @param null $key key
     * @return array|string
     */
    public static function kvEnable($key = null)
    {
        $desc = [
            self::DISABLE => '禁用',
            self::ENABLE  => '启用',
        ];

        return kv($desc, $key);
    }

    /**
     * 检测表是否存在
     * @param string $table 检测的表的名称
     * @return mixed
     */
    public static function tableExists(string $table)
    {
        $statusKey  = 'weiran-system::db.table_status';
        $expiredKey = 'weiran-system::db.table_expired';

        $tbStatus = (array) sys_setting($statusKey, []);
        $expired  = (int) sys_setting($expiredKey, 0);

        if (!isset($tbStatus[$table]) || !$expired || $expired <= Carbon::now()->timestamp) {
            app('weiran.system.setting')->set($expiredKey, Carbon::now()->addMinutes(600)->timestamp);

            // 重新查询表格是否存在
            $hasTable         = DB::getSchemaBuilder()->hasTable($table);
            $tbStatus[$table] = $hasTable;
            app('weiran.system.setting')->set($statusKey, $tbStatus);
        }
        return $tbStatus[$table];
    }
}