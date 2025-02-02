<?php

declare(strict_types = 1);

namespace Weiran\System\Setting\Repository;

use Exception;
use Illuminate\Support\Str;
use PDOException;
use Weiran\Core\Classes\Contracts\SettingContract;
use Weiran\Core\Redis\RdsDb;
use Weiran\Framework\Classes\Traits\AppTrait;
use Weiran\Framework\Classes\Traits\KeyParserTrait;
use Weiran\System\Classes\PySystemDef;
use Weiran\System\Exceptions\SettingKeyNotMatchException;
use Weiran\System\Exceptions\SettingValueOutOfRangeException;
use Weiran\System\Models\SysConfig;
use Throwable;

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
            self::$rds = sys_tag('py-system');
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
            self::$rds->hDel(PySystemDef::ckSetting(), $this->convertKey($key));
            $record->delete();
        }
        return true;
    }

    /**
     * @inheritDoc
     * @throws SettingKeyNotMatchException
     * @throws SettingValueOutOfRangeException
     */
    public function get(string $key, $default = '')
    {
        if (!$this->keyParserMatch($key)) {
            return $default;
        }

        if ($val = self::$rds->hGet(PySystemDef::ckSetting(), $this->convertKey($key), false)) {
            return unserialize($val);
        }

        /* 4.2 : fix skeleton migrate error
         * ---------------------------------------- */
        if (!self::$existTable) {
            return $default;
        }

        try {
            $record = $this->findRecord($key);
        } catch (PDOException $e) {
            self::$existTable = false;
            return $default;
        }
        if (!$record) {
            $this->set($key, $default);
            return $default;
        }

        self::$rds->hSet(PySystemDef::ckSetting(), $this->convertKey($key), $record->value);

        return unserialize($record->value);
    }

    /**
     * @inheritDoc
     * @throws SettingKeyNotMatchException
     * @throws SettingValueOutOfRangeException
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

        $record         = $this->findRecord($key);
        $serializeValue = serialize($value);
        if (strlen($serializeValue) >= 65535) {
            throw (new SettingValueOutOfRangeException($key))->setContext(compact('key'));
        }
        if (!$record) {
            [$namespace, $group, $item] = $this->parseKey($key);
            SysConfig::create([
                'namespace' => $namespace,
                'group'     => $group,
                'item'      => $item,
                'value'     => $serializeValue,
            ]);
        }
        else {
            $record->value = $serializeValue;
            $record->save();
        }

        self::$rds->hSet(PySystemDef::ckSetting(), $this->convertKey($key), $serializeValue);
        return true;
    }

    /**
     * 根据命名空间从数据库中获取数据
     * @param string $ng 命名空间和分组
     * @return array
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
            $data->put($item['item'], unserialize($item['value']));
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
            self::$rds->hDel(PySystemDef::ckSetting(), $keys);
            try {
                $Db->delete();
                return true;
            } catch (Throwable $e) {
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
        self::$rds->del(PySystemDef::ckSetting());
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
