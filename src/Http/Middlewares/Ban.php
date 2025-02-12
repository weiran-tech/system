<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Middlewares;

use Closure;
use Weiran\Framework\Classes\Resp;
use Weiran\Framework\Helper\EnvHelper;
use Weiran\System\Action\Ban as ActBan;
use Weiran\System\Models\PamBan;
use Weiran\System\Models\SysConfig;

/**
 * 禁止访问, 对于用户访问的控制
 * 如果是前台用户, 放到所有请求之前
 * 如果是后台用户, 放到所有请求之后(需要放过管理员)
 */
class Ban
{

    /**
     * @param         $request
     * @param Closure $next
     * @param string  $type 账号类型, 用于封禁
     * @return mixed
     */
    public function handle($request, Closure $next, string $type = 'user')
    {
        //获取ip
        $ip = EnvHelper::ip();

        if ($appType = x_header('type')) {
            $type = $appType;

        }

        $status  = sys_setting('wr-system::ban.status-' . $type, SysConfig::STR_NO);
        $isBlack = sys_setting('wr-system::ban.type-' . $type, PamBan::WB_TYPE_BLACK) === PamBan::WB_TYPE_BLACK;

        /* 未开启风险拦截
         * ---------------------------------------- */
        if ($status !== SysConfig::STR_YES) {
            return $next($request);
        }

        $Ban  = new ActBan();
        $ipIn = $Ban->checkIn($type, PamBan::TYPE_IP, $ip);

        /* 黑名单策略, 黑名单, Ip In : 封禁
         * ---------------------------------------- */
        if ($isBlack && $ipIn) {
            return Resp::error("当前 ip '{$ip}' 被封禁，请联系客服处理");
        }

        /* 白名单策略, 不在列表内, 提示
         * ---------------------------------------- */
        if (!$isBlack && !$ipIn) {
            return Resp::error("当前ip '{$ip}' 不允许访问，请联系客服处理");
        }


        $deviceId = x_header('id') ?: input('device_id');
        if ($deviceId && PamBan::banDeviceIsOpen($type) === 'Y') {
            $deviceIn = $Ban->checkIn($type, PamBan::TYPE_DEVICE, $deviceId);
            /* 黑名单策略, 设备In : 封禁
             * ---------------------------------------- */
            if ($isBlack && $deviceIn) {
                return Resp::error('当前设备被封禁，请联系客服处理');
            }

            /* 白名单策略, 设备不在列表中, 封禁
             * ---------------------------------------- */
            if (!$isBlack && !$deviceIn) {
                $maps = [
                    'user'    => '用户',
                    'backend' => '后台',
                ];
                return Resp::error('当前设备不在' . ($maps[$type] ?? '') . '白名单中, 不允许访问');
            }
        }

        return $next($request);
    }
}