<?php

declare(strict_types = 1);

namespace Weiran\System\Jobs;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Weiran\Framework\Application\Job;
use Weiran\Framework\Exceptions\ApplicationException;
use Psr\Http\Message\ResponseInterface;

/**
 * 增强型的 Guzzle 调用
 * @since 4.1 , 传参遵循 guzzle options 加强自定义
 */
class NotifyProJob extends Job implements ShouldQueue
{
    use Queueable;

    /**
     * @var string 请求网址
     */
    private string $url;

    /**
     * @var string 请求方法
     */
    private string $method;

    /**
     * @var array 请求参数
     */
    private array $options;

    /**
     * 延迟总次数
     * @var int
     */
    private int $delayTimes;

    /**
     * 默认的当前执行点位
     * @var int
     */
    private int $execAt = 0;


    /**
     * 重发的延迟时间
     * @var array|int[]
     */
    private array $delayMap = [
        15, 45, 120, 300,
    ];

    /**
     * 下一次执行点位
     * @var int
     */
    private int $nextExecAt;

    /**
     * 统计用户计算数量
     * @param string $url         请求的URL 地址
     * @param string $method      请求的方法
     * @param array  $options     请求的参数
     * @param int    $delay_times 请求次数
     * @throws ApplicationException
     */
    public function __construct(string $url, string $method, array $options = [], int $delay_times = 0)
    {
        $this->url        = $url;
        $this->method     = strtoupper($method);
        $this->options    = $options;
        $this->delayTimes = $delay_times;

        // 大于 1 次时候进行验证
        if ($delay_times >= 1) {
            if (!isset($this->delayMap[$delay_times - 1])) {
                $times = count($this->delayMap);
                throw new ApplicationException("延迟次数超出延迟定义, 当前最多允许执行 {$times} 次, 请重新设定延迟执行次数");
            }
        }
    }

    /**
     * 执行
     * @throws ApplicationException
     */
    public function handle()
    {
        /* 重发次数
         -------------------------------------------- */

        $curl    = new Client();
        $options = [
            'timeout' => 10,
        ];
        try {
            $resp = $curl->request($this->method, $this->url, array_merge($options, $this->options));
            $this->log($resp);
        } catch (GuzzleException $e) {
            if ($this->canDelay()) {
                dispatch(
                    (new self($this->url, $this->method, $this->options, $this->delayTimes))
                        ->delay($this->delayMap[$this->execAt])
                        ->setExecAt($this->nextExecAt)
                );
            }
            $this->log($e, false);
        }
    }

    /**
     * 设置执行次数
     * @param $time
     * @return self
     */
    public function setExecAt($time): self
    {
        $this->execAt = $time;
        return $this;
    }

    /**
     * 是否可以延迟执行
     * @return void
     */
    private function canDelay(): bool
    {
        // 下次执行大于总可执行次数, 不可延迟
        if (($this->execAt + 1) > $this->delayTimes) {
            return false;
        }

        $this->nextExecAt = $this->execAt + 1;
        return true;
    }

    /**
     * 生成记录日志
     * @param GuzzleException|ResponseInterface $result
     * @param bool                              $is_success
     * @return void
     */
    private function log($result, bool $is_success = true): void
    {
        $resp = '';
        if ($result instanceof ResponseInterface) {
            $resp = $result->getBody()->getContents();
        }
        if ($result instanceof GuzzleException) {
            $resp = $result->getMessage();
        }
        $options = json_encode($this->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $tip = '';
        if (!$is_success && $this->canDelay()) {
            $delaySeconds   = $this->delayMap[$this->execAt];
            $delaySecondsAt = Carbon::now()->addSeconds($this->delayMap[$this->execAt])->toDateTimeString();
            $tip            = "Next request will exec at {$delaySeconds}s later, at {$delaySecondsAt}";
        }

        $mark  = md5($this->method . $this->url . $options);
        $total = $is_success ? 1 : $this->delayTimes + 1;

        $msg = ($this->execAt === 0 ? "1/{$total} [{$mark}] Request: " : $this->execAt + 1 . "/{$total} [{$mark}] Request: ") . PHP_EOL .
            "Url : [{$this->method}] {$this->url}" . PHP_EOL .
            "Options : {$options}" . PHP_EOL .
            "Result : {$resp} " .
            ($tip ? PHP_EOL . "Tip : {$tip}" : '');
        if ($is_success) {
            sys_info(self::class, $msg);
        }
        else {
            sys_error(self::class, $msg);
        }
    }
}
