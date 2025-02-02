<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Traits;

use DB;
use Illuminate\Support\Str;
use Weiran\Framework\Classes\ConsoleTable;

/**
 * Db Trait Db 工具
 */
trait DbTrait
{

    /**
     * 对数据进行批量设置
     * @param string                       $table  表名
     * @param array<int|string,int|string> $values 排序信息
     * @param string                       $field  需要批量更新的字段
     * @param string                       $key    key
     * @return void
     */
    public function fieldVals(string $table, array $values, string $field, string $key = 'id')
    {
        $sql = "UPDATE {$table} SET `{$field}` = CASE `{$key}` ";
        foreach ($values as $id => $val) {
            $sql .= " WHEN {$id} THEN {$val} ";
        }
        $sql .= sprintf(' END WHERE %s in (%s) ', $key, implode(',', array_keys($values)));
        DB::statement($sql);
    }

    /**
     * 更新数据库字段值
     * @param string $table 数据表名称
     * @param int    $id    ID
     * @param string $field 更新字段
     * @param string $val   更新值
     * @see        fieldVals
     * @deprecated 4.1
     */
    public function fieldVal(string $table, int $id, string $field, string $val)
    {
        DB::table($table)->where('id', $id)->update([
            $field => $val,
        ]);
    }

    /**
     * 检查当前是否是在事务中
     * @return bool
     */
    protected function inTransaction(): bool
    {
        if (DB::transactionLevel() <= 0) {
            return $this->setError('当前操作未在事务中');
        }

        return true;
    }

    /**
     * 启用 查询日志
     */
    protected function enableQueryLog(): void
    {
        DB::enableQueryLog();
    }

    /**
     * 禁用查询日志
     * @since 4.1
     */
    protected function disableQueryLog(): void
    {
        DB::enableQueryLog();
    }

    /**
     * 重新启用查询日志
     * @since 4.1
     */
    protected function reEnableQueryLog(): void
    {
        DB::disableQueryLog();
        DB::flushQueryLog();
        DB::enableQueryLog();
    }

    /**
     * 获取SqlLog
     * @return array
     */
    protected function fetchQueryLog(): array
    {
        $logs = DB::getQueryLog();
        if (count($logs)) {
            $formats = [];
            foreach ($logs as $log) {
                $query = $log['query'];
                if (count($log['bindings'] ?? [])) {
                    foreach ($log['bindings'] as $binding) {
                        if (is_string($binding)) {
                            $binding = '"' . $binding . '"';
                        }
                        $query = Str::replaceFirst('?', $binding, $query);
                    }
                }
                $time      = $log['time'] ?? 0;
                $formats[] = [
                    $query, $time,
                ];
            }
            return $formats;
        }
        return $logs;
    }

    /**
     * SQL Log 提示
     * @since 4.1
     */
    protected function printQueryLog(): void
    {
        $logs = $this->fetchQueryLog();

        if (count($logs)) {
            $Table = new ConsoleTable();
            $Table->headers([
                'Query', 'Time',
            ])->rows($logs);
            $Table->display();
        }
    }
}