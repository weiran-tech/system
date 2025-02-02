<?php

namespace Weiran\System\Tests\Base;

use DB;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Log;
use Weiran\Framework\Application\TestCase;
use Weiran\Framework\Classes\ConsoleTable;
use Weiran\Framework\Classes\Traits\AppTrait;
use Weiran\Framework\Helper\StrHelper;
use Weiran\System\Classes\Traits\DbTrait;
use Weiran\System\Models\PamAccount;
use Throwable;

/**
 * @see        TestCase
 * @removed    5.0
 * @deprecated 4.1
 */
class SystemTestCase extends TestCase
{

    use DbTrait, AppTrait;

    /**
     * 是否开启数据库存入
     * @var bool
     */
    protected bool $enableDb = false;

    /**
     * 临时指定账号
     * @var PamAccount|null
     */
    protected ?PamAccount $pam;

    /**
     * 控制台输出
     * @var array
     */
    protected array $reportType = ['log', 'console'];

    public function setUp(): void
    {
        parent::setUp();
        DB::enableQueryLog();
        if (!$this->enableDb) {
            try {
                DB::beginTransaction();
            } catch (Exception $e) {
                $this->runLog(false, $e->getMessage());
            }
        }
    }


    public function tearDown(): void
    {
        if (!$this->enableDb) {
            try {
                DB::rollBack();
                parent::tearDown();
            } catch (Throwable $e) {
                $this->runLog(false, $e->getMessage());
            }
        }
    }

    /**
     * 测试日志
     * @param bool   $result  测试结果
     * @param string $message 测试消息
     * @param mixed  $context 上下文信息, 数组
     * @return string
     */
    public function runLog(bool $result = true, string $message = '', $context = null): string
    {
        $type    = $result ? '[Success]' : '[Error]';
        $message = 'Test : ' . $type . $message;
        if ($context instanceof Arrayable) {
            $context = $context->toArray();
        }
        if (in_array('log', $this->reportType(), true)) {
            Log::info($message, $context ?: []);
        }
        if (in_array('console', $this->reportType(), true)) {
            var_dump([
                'message' => $message,
                'context' => $context ?: [],
            ]);
        }
        return $message;
    }


    protected function initPam($username = '')
    {
        $username = $username ?: $this->env('pam');
        $pam      = PamAccount::passport($username);
        $this->assertNotNull($pam, 'Testing user pam is not exist');
        $this->pam = $pam;
    }

    /**
     * 获取环境变量
     * @param string $key
     * @param string $default
     * @return mixed|string
     */
    protected function env(string $key = '', string $default = ''): string
    {
        if (!$key) {
            return '';
        }
        return env('TESTING_' . strtoupper($key), $default);
    }

    /**
     * 汇报类型
     * @return array
     */
    protected function reportType(): array
    {
        $reportType = $this->env('report_type');
        if ($reportType) {
            return StrHelper::separate(',', $reportType);
        }

        return $this->reportType;
    }

    /**
     * SQL Log 提示
     */
    protected function sqlLog(): void
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

    /**
     * 当前的描述
     * @param string $append 追加的信息
     * @return string
     */
    protected static function desc(string $append = ''): string
    {
        $bt       = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $function = $bt[1]['function'];
        $class    = $bt[1]['class'];

        return '|' . $class . '@' . $function . '|' . ($append ? $append . '|' : '');
    }
}