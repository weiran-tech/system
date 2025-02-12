<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Traits;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Weiran\Framework\Classes\Resp;
use Weiran\Framework\Exceptions\ApplicationException;

/**
 * @method max($num = null)  最大值, 如果没有则进行设置
 * @method min($num = null)  设置执行的最小值
 * @method start($num = null, $force = false)  设置执行的起始值, 默认是最小值
 * @method cached($bool = true)  设置执行缓存标识
 * @method total($num = null)  设置执行的总数量
 * @method lastId($num = null, $force = false)  执行ID值(用于展示)
 * @method left($num = null, $force = false)  设置剩余值(用于展示)
 */
trait FixTrait
{
    /**
     * @var array $fix fix
     */
    protected $fix = [
        'max'      => 0,
        'min'      => 0,
        'section'  => 1,
        'start'    => 1,
        'cached'   => 0,
        'total'    => 0,
        'lastId'   => 0,
        'left'     => 0,
        'interval' => 500,
        'method'   => '',
    ];

    /**
     * @param $method
     * @param $params
     * @return int|bool
     * @throws ApplicationException
     */
    public function __call($method, $params)
    {
        // dd($params);
        if (!isset($this->fix[$method])) {
            throw new ApplicationException('Error Fix Define');
        }

        if (count($params) === 0) {
            return $this->fix[$method];
        }
        if (count($params) === 1) {
            if (!$this->fix[$method]) {
                $this->fix[$method] = $params[0];
            }
        }

        // force update
        if (count($params) === 2) {
            $this->fix[$method] = $params[0];
        }

        return $this->fix[$method];
    }

    /**
     * 更改每次执行的数据量
     * @param $num
     * @return int
     */
    protected function section($num = 0): int
    {
        if ($num) {
            $this->fix['section'] = $num;
        }
        return $this->fix['section'];
    }

    /**
     * 更改每次执行的时间间隔
     * @param $num
     * @return int
     */
    protected function interval($num = 0): int
    {
        if ($num) {
            $this->fix['interval'] = $num;
        }
        return $this->fix['interval'];
    }

    /**
     * 初始化
     */
    protected function fixInit()
    {
        // 最大id
        $this->fix['max'] = input('max', 0);
        // 最小id
        $this->fix['min'] = input('min', 0);
        // 每次更新数量
        $this->fix['section'] = (int) (input('section', 1) ?: 1);
        // 需要更新的 start_id
        $this->fix['start'] = (int) (input('start', 0) ?: 1);
        // 是否缓存过
        $this->fix['cached'] = input('cache', 0);
        // 需要更新的总量
        $this->fix['total'] = input('total', 0);
    }

    /**
     * 返回修复的页面
     * @return Factory|View
     */
    protected function fixView()
    {
        if ($this->fix['total']) {
            $percentage = round((($this->fix['total'] - $this->fix['left']) / $this->fix['total']) * 100);
        }
        else {
            $percentage = '0';
        }

        $url = route_url('', null, [
            'max'     => $this->fix['max'],
            'min'     => $this->fix['min'],
            'section' => $this->fix['section'],
            'start'   => $this->fix['lastId'],
            'total'   => $this->fix['total'],
            'cache'   => $this->fix['cached'],
            'method'  => $this->fix['method'],
        ]);

        return view('wr-mgr-page::tpl.progress', [
            'total'         => $this->fix['total'],
            'section'       => $this->fix['section'],
            'left'          => $this->fix['left'],
            'percentage'    => $percentage,
            'continue_time' => $this->fix['interval'], // ms 毫秒
            'continue_url'  => $url,
        ]);
    }

    /**
     * 返回修复的下一次请求
     */
    protected function fixResp()
    {
        if ($this->fix['total']) {
            $percentage = round((($this->fix['total'] - $this->fix['left']) / $this->fix['total']) * 100);
        }
        else {
            $percentage = 0;
        }

        $url = route_url('', null, [
            'max'     => $this->fix['max'],
            'min'     => $this->fix['min'],
            'section' => $this->fix['section'],
            'start'   => $this->fix['lastId'],
            'total'   => $this->fix['total'],
            'cache'   => $this->fix['cached'],
            'method'  => $this->fix['method'],
        ]);

        return Resp::success('更新中', [
            'total'         => $this->fix['total'],
            'section'       => $this->fix['section'],
            'left'          => $this->fix['left'],
            'percentage'    => $percentage,
            'continue_time' => $this->fix['interval'], // ms 毫秒
            'continue_url'  => $url,
        ]);
    }
}