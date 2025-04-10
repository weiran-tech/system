<?php

declare(strict_types = 1);

namespace Weiran\System\Setting\Repository;

use Exception;
use Illuminate\Support\Str;
use JsonException;
use PDOException;
use Throwable;
use Weiran\Core\Classes\Contracts\SettingContract;
use Weiran\Core\Redis\RdsDb;
use Weiran\Framework\Classes\Traits\AppTrait;
use Weiran\Framework\Classes\Traits\KeyParserTrait;
use Weiran\System\Classes\WeiranSystemDef;
use Weiran\System\Exceptions\SettingKeyNotMatchException;
use Weiran\System\Exceptions\SettingValueOutOfRangeException;
use Weiran\System\Models\SysConfig;

/**
 * system config
 * Setting Repository
 */
class SettingRepository implements SettingContract
{
    use KeyParserTrait, AppTrait;

    /**
     * @var RdsDb|null
     */
    private static ?RdsDb $rds = null;


    /**
     * 是否存在数据表
     * @var bool
     */
    private static bool $existTable = true;

    public function __construct()
    {
        if (!self::$rds) {
            self::$rds = sys_tag('weiran-system');
        }
    }

    /**
     * @inheritDoc
     * @throws SettingKeyNotMatchException
     * @throws Exception
     */
    public function delete(string $key): bool
    {
        if (!$this->keyParserMatch($key)) {
            throw (new SettingKeyNotMatchException($key))->setContext(compact('key'));
        }
        $record = $this->findRecord($key);
        if ($record) {
            self::$rds->hDel(WeiranSystemDef::ckSetting(), $this->convertKey($key));
            $record->delete();
        }
        return true;
    }

    /**
     * @inheritDoc
     * @throws SettingKeyNotMatchException
     * @throws SettingValueOutOfRangeException|JsonException
     */
    public function get(string $key, $default = '')
    {
        if (!$this->keyParserMatch($key)) {
            return $default;
        }

        if ($val = self::$rds->hGet(WeiranSystemDef::ckSetting(), $this->convertKey($key), false)) {
            return json_decode($val, true, 512, JSON_THROW_ON_ERROR);
        }

        /* 4.2 : fix skeleton migrate error
         * ---------------------------------------- */
        if (!self::$existTable) {
            return $default;
        }

        try {
            $record = $this->findRecord($key);
        } catch (PDOException) {
            self::$existTable = false;
            return $default;
        }
        if (!$record) {
            $this->set($key, $default);
            return $default;
        }

        self::$rds->hSet(WeiranSystemDef::ckSetting(), $this->convertKey($key), $record->value);

        return json_decode($record->value, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @inheritDoc
     * @throws SettingKeyNotMatchException
     * @throws SettingValueOutOfRangeException
     * @throws JsonException
     */
    public function set($key, $value = ''): bool
    {
        if (is_array($key)) {
            foreach ($key as $_key => $_value) {
                $this->set($_key, $_value);
            }
            return true;
        }

        if (!$this->keyParserMatch($key)) {
            throw (new SettingKeyNotMatchException($key))->setContext(compact('key'));
        }

        $record       = $this->findRecord($key);
        $encodedValue = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($encodedValue) >= 65535) {
            throw (new SettingValueOutOfRangeException($key))->setContext(compact('key'));
        }
        if (!$record) {
            [$namespace, $group, $item] = $this->parseKey($key);
            SysConfig::create([
                'namespace' => $namespace,
                'group'     => $group,
                'item'      => $item,
                'value'     => $encodedValue,
            ]);
        }
        else {
            $record->value = $encodedValue;
            $record->save();
        }

        self::$rds->hSet(WeiranSystemDef::ckSetting(), $this->convertKey($key), $encodedValue);
        return true;
    }

    /**
     * 根据命名空间从数据库中获取数据
     * @param string $ng 命名空间和分组
     * @return array
     * @throws JsonException
     */
    public function getNG(string $ng): array
    {
        [$ns, $group] = explode('::', $ng);
        if (!$ns || !$group) {
            return [];
        }
        $values = SysConfig::where('namespace', $ns)->where('group', $group)->select(['item', 'value'])->get();
        $data   = collect();
        $values->each(function ($item) use ($data) {
            $data->put($item['item'], json_decode($item['value'], true, 512, JSON_THROW_ON_ERROR));
        });

        return $data->toArray();
    }

    /**
     * 删除命名空间以及分组
     * @param string $ng
     * @return bool
     */
    public function removeNG(string $ng): bool
    {
        if (!Str::contains($ng, '::')) {
            return false;
        }
        [$ns, $group] = explode('::', $ng);
        if (!$ns && !$group) {
            return false;
        }
        $Db     = SysConfig::where('namespace', $ns)->where('group', $group);
        $values = (clone $Db)->pluck('item');
        if ($values->count()) {
            $keys = [];
            $values->each(function ($item) use ($ns, $group, &$keys) {
                $keys[] = $this->convertKey("{$ns}::{$group}.{$item}");
            });
            self::$rds->hDel(WeiranSystemDef::ckSetting(), $keys);
            try {
                $Db->delete();
                return true;
            } catch (Throwable) {
                return false;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        self::$rds->del(WeiranSystemDef::ckSetting());
    }

    /**
     * 转换 KEY
     * @param $key
     * @return string
     */
    private function convertKey($key): string
    {
        return str_replace(['::', '.'], ['--', '-'], $key);
    }

    /**
     * Returns a record (cached)
     * @param string $key 获取的key
     * @return SysConfig|null
     */
    private function findRecord(string $key): ?SysConfig
    {
        /** @var SysConfig $record */
        $record = SysConfig::query();

        return $record->applyKey($key)->first();
    }
}
