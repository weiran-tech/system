<?php

declare(strict_types = 1);

namespace Weiran\System\Action;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Str;
use Weiran\System\Classes\PySystemDef;

/**
 * Apidoc 读取
 */
class DbOptimize
{

    /**
     * 是否开启
     * @return bool
     */
    public function isOpen(): bool
    {
        return sys_tag('py-system-persist')->get(PySystemDef::ckDbOptimize('is_open')) === 'Y';
    }

    /**
     * 以为 sys_config 会触发循环请求, 所以这里不能记录
     */
    public function log(QueryExecuted $event): bool
    {
        static $isOpen;
        static $opened = null;
        if (!isset($isOpen)) {
            $isOpen = $this->isOpen();
        }

        if (!$isOpen) {
            return false;
        }

        if (is_null($opened)) {
            $opened = (array) sys_tag('py-system-persist')->hGetAll(PySystemDef::ckDbOptimize('on'));
        }

        if (!count($opened)) {
            return false;
        }

        $isOpen     = false;
        $eventTable = '';
        foreach ($opened as $table => $open) {
            if (!$isOpen && Str::contains($event->sql, $table)) {
                $isOpen     = true;
                $eventTable = $table;
            }
        }

        if (!$isOpen) {
            return false;
        }

        $md5Key = md5($event->sql);
        $tbKey  = PySystemDef::ckDbOptimize($eventTable);
        if (sys_tag('py-system-persist')->hExists($tbKey, $md5Key)) {
            return false;
        }
        sys_tag('py-system-persist')->hSet($tbKey, $md5Key, [
            'sql'      => $event->sql,
            'bindings' => $event->bindings,
            'time'     => $event->time,
        ]);
        return true;
    }


}