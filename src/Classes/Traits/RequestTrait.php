<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Traits;

/**
 * 请求 Trait
 */
trait RequestTrait
{
    /**
     * @var array 请求日志
     */
    protected static $reqLog;

    /**
     * @var string 请求的URL地址
     */
    protected $reqUrl;

    /**
     * @var string 请求响应信息
     */
    protected $resp;

    /**
     * @var string 请求参数
     */
    protected $param;

    /**
     * @return mixed
     */
    public function getReqUrl()
    {
        return $this->reqUrl;
    }

    /**
     * @param mixed $reqUrl 请求地址
     */
    public function setReqUrl($reqUrl)
    {
        $this->reqUrl = $reqUrl;
    }

    /**
     * @return mixed
     */
    public function getResp()
    {
        return $this->resp;
    }

    /**
     * @param mixed $result 设置返回信息
     */
    public function setResp($result)
    {
        $this->resp = $result;
    }

    /**
     * @return mixed
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * @param mixed $param 参数
     */
    public function setParam($param)
    {
        $this->param = $param;
    }

    /**
     * 获取所有请求数据返回数据
     * @return array
     */
    public function getReqResp(): array
    {
        return [
            'url'    => $this->getReqUrl(),
            'param'  => $this->getParam(),
            'result' => $this->getResp(),
            'uri'    => $this->getReqUrl() . '?' . http_build_query($this->getParam()),
        ];
    }

    /**
     * 获取请求日志
     * @return array
     */
    public function getReqLog()
    {
        return self::$reqLog;
    }

    /**
     * 写入日志
     */
    protected function writeLog()
    {
        self::$reqLog[] = $this->getReqResp();
    }
}