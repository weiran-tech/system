<?php

declare(strict_types = 1);

namespace Weiran\System\Action;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use IPLib\Factory;
use Weiran\Core\Redis\RdsDb;
use Weiran\Framework\Classes\Traits\AppTrait;
use Weiran\Framework\Helper\UtilHelper;
use Weiran\System\Classes\PySystemDef;
use Weiran\System\Events\PamTokenBanEvent;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamBan;
use Weiran\System\Models\PamToken;
use Throwable;

/**
 * 用户禁用
 */
class Ban
{
    use AppTrait;

    private static RdsDb $rds;

    public function __construct()
    {
        self::$rds = sys_tag('py-system');
    }

    /**
     *  封禁
     * @param array $input
     * @return bool
     */
    public function establish(array $input): bool
    {
        $account_type = data_get($input, 'account_type', '');
        $type         = data_get($input, 'type', '');
        $value        = trim(data_get($input, 'value', ''));
        $note         = trim(data_get($input, 'note', ''));

        if (!array_key_exists($account_type, PamAccount::kvType())) {
            return $this->setError('请填写正确的账户类型');
        }

        $DbBan = PamBan::where('account_type', $account_type);
        if (!array_key_exists($type, PamBan::kvType())) {
            return $this->setError('请选择正确的类型');
        }

        $isRange = false;
        $startIp = 0;
        $endIp   = 0;
        // ip 合法性
        if ($type === PamBan::TYPE_IP) {
            // ip 范围 : 192.168.1.21-192.168.1.255
            if (!$passed = $this->parseIpRange($value)) {
                return false;
            }
            [$isRange, $startIp, $endIp] = $passed;

            $DbIp  = (clone $DbBan)->where('type', PamBan::TYPE_IP);
            $first = (clone $DbIp)->where(function ($q) use ($startIp, $endIp) {
                $q->orWhere(function ($q) use ($startIp) {
                    $q->where('ip_start', '<=', $startIp)->where('ip_end', '>=', $startIp);
                });
                $q->orWhere(function ($q) use ($endIp) {
                    $q->where('ip_start', '<=', $endIp)->where('ip_end', '>=', $endIp);
                });
            })->first();
            if ($first) {
                return $this->setError('此 IP 和 ' . $first->value . ' 存在IP段重复, 请检查后再添加');
            }
        }
        else if ((clone $DbBan)->where('type', PamBan::TYPE_DEVICE)->where('value', $value)->exists()) {
            return $this->setError('封禁设备已存在!');
        }

        $item = PamBan::create([
            'account_type' => $account_type,
            'type'         => $type,
            'value'        => $value,
            'ip_start'     => $startIp,
            'ip_end'       => $endIp,
            'note'         => $note,
        ]);

        if ($isRange) {
            $this->saveRanges(PySystemDef::ckBanIpRange($account_type), collect([$item]));
        }
        else {
            $this->saveOnes(PySystemDef::ckBanOne($account_type), collect([$item]));
        }
        return true;
    }

    /**
     * 删除
     * @param int $id id
     * @return bool
     */
    public function delete(int $id): bool
    {
        if (!$ban = PamBan::find($id)) {
            return $this->setError('条目不存在');
        }

        try {
            $isRange = false;
            if ($ban->type === PamBan::TYPE_IP) {
                [$isRange] = $this->parseIpRange($ban->value);
            }

            if ($isRange) {
                $this->removeRanges(PySystemDef::ckBanIpRange($ban->account_type), collect([$ban]));
            }
            else {
                $this->removeOnes(PySystemDef::ckBanOne($ban->account_type), collect([$ban]));
            }

            $ban->delete();
            return true;
        } catch (Exception $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 检测给定内容是否在缓存中
     * @param string $account_type 账号类型
     * @param string $type         需要检测的类型
     * @param string $value        需要检测的值
     * @return bool
     */
    public function checkIn(string $account_type, string $type, string $value): bool
    {
        $oneKey    = PySystemDef::ckBanOne($account_type);
        $rangesKey = PySystemDef::ckBanIpRange($account_type);
        if (!self::$rds->exists($oneKey) || !self::$rds->exists($rangesKey)) {
            $this->initAccountType($account_type);
        }

        // 存在固定的设备类型或者是固定的IP
        $hashKey = "{$type}|{$value}";
        if (self::$rds->hExists($oneKey, $hashKey)) {
            return true;
        }

        // 检测是否窜在范围中
        if ($type === PamBan::TYPE_IP) {
            // check in one
            $ipLong  = ip2long($value);
            $members = self::$rds->sMembers($rangesKey);
            if (!$members) {
                return false;
            }
            foreach ($members as $member) {
                if (!Str::contains($member, '|')) {
                    continue;
                }
                $mExp = explode('|', $member);
                [$start, $end] = explode('-', $mExp[1]);
                if ($start <= $ipLong && $ipLong <= $end) {
                    return true;
                }
            }
            return false;
        }
        return false;
    }

    /**
     * 禁用 Ban
     * @param $id
     * @param $type
     * @return bool
     */
    public function type($id, $type): bool
    {
        /** @var PamToken $item */
        $item = PamToken::find($id);
        if (!array_key_exists($type, PamBan::kvType())) {
            return $this->setError('封禁类型错误');
        }

        if (!$this->establish([
            'account_type' => PamAccount::TYPE_USER,
            'type'         => $type,
            'value'        => $type === PamBan::TYPE_IP ? $item->login_ip : $item->device_id,
        ])) {
            return false;
        }

        try {
            $item->delete();
            event(new PamTokenBanEvent($item, $type));
        } catch (Throwable $e) {
            return $this->setError($e->getMessage());
        }
        return true;
    }

    /**
     * 初始化所有
     */
    public function initCache(): void
    {
        foreach (PamAccount::kvType() as $key => $value) {
            $this->initAccountType($key);
        }
    }

    public function parseIpRange($value)
    {
        $isRange = false;
        // ip 范围 : 192.168.1.21-192.168.1.255
        if (Str::contains($value, '-')) {
            [$start, $end] = explode('-', $value);
            if (is_null(Factory::getRangesFromBoundaries($start, $end))) {
                return $this->setError('错误的IP段写法');
            }
            $isRange = true;
            $startIp = ip2long($start);
            $endIp   = ip2long($end);
        }
        //  192.168.1.*
        else if (Str::contains($value, '*') || Str::contains($value, '/')) {
            if (is_null($range = Factory::parseRangeString($value))) {
                return $this->setError('错误的IP格式写法');
            }
            $isRange = true;
            $startIp = ip2long((string) $range->getStartAddress());
            $endIp   = ip2long((string) $range->getEndAddress());
        }
        // 192.168.1.1
        else {
            if (!UtilHelper::isIp($value)) {
                return $this->setError('IP地址不合法');
            }
            $startIp = ip2long($value);
            $endIp   = ip2long($value);
        }
        return [
            $isRange, $startIp, $endIp,
        ];
    }

    /**
     * 数据重新初始化到缓存中
     * @param string $account_type 账号类型
     */
    private function initAccountType(string $account_type): void
    {
        $items  = PamBan::where('account_type', $account_type)->get();
        $ones   = collect();
        $ranges = collect();
        collect($items)->each(function ($item) use ($ones, $ranges) {
            if (
                // 单设备
                $item->type === PamBan::TYPE_DEVICE
                ||
                // 单IP
                ($item->type === PamBan::TYPE_IP && UtilHelper::isIp($item->value))
            ) {
                $ones->push($item);
            }
            else {
                $ranges->push($item);
            }
        });
        $this->initOne($account_type, $ones);
        $this->initRanges($account_type, $ranges);
    }

    /**
     * 初始化Ip/设备
     * @param string     $account_type
     * @param Collection $items
     */
    private function initOne(string $account_type, Collection $items): void
    {
        $key = PySystemDef::ckBanOne($account_type);
        self::$rds->del($key);
        // 保障KEY存在
        self::$rds->hSet($key, 'init|duoli', 'duoli' . '|init|' . Carbon::now()->toDateTimeString());
        $this->saveOnes($key, $items);
    }


    /**
     * 初始化范围
     * @param string     $account_type 账号类型
     * @param Collection $items
     */
    private function initRanges(string $account_type, Collection $items): void
    {
        $key = PySystemDef::ckBanIpRange($account_type);
        self::$rds->del($key);
        // 保障KEY存在
        self::$rds->sAdd($key, [
            'range-0',
        ]);
        $this->saveRanges($key, $items);
    }

    /**
     * 保存IP段数据
     * @param string     $key
     * @param Collection $items
     */
    private function saveRanges(string $key, Collection $items): void
    {
        $ranges = $this->ranges($items);
        if ($ranges->count()) {
            self::$rds->sAdd($key, $ranges->toArray());
        }
    }

    /**
     * 移除范围值
     * @param string     $key
     * @param Collection $items
     */
    private function removeRanges(string $key, Collection $items): void
    {
        $ranges = $this->ranges($items);
        if ($ranges->count()) {
            self::$rds->sRem($key, $ranges->toArray());
        }
    }

    /**
     * 获取范围值
     * @param Collection $items
     * @return Collection
     */
    private function ranges(Collection $items): Collection
    {
        $ranges = collect();
        collect($items)->each(function ($item) use ($ranges) {
            $value = $item->value;
            [, $startIp, $endIp] = $this->parseIpRange($value);
            $ranges->push("range-{$item->id}|{$startIp}-{$endIp}");
        });
        return $ranges;
    }


    /**
     * 保存单条数据
     * @param string     $key
     * @param Collection $items
     * @param string     $type
     */
    private function saveOnes(string $key, Collection $items, string $type = 'init'): void
    {
        $ones = $this->ones($items, $type);
        if ($ones->count()) {
            self::$rds->hMSet($key, $ones->toArray());
        }
    }

    /**
     * 移除指定的设备类型
     * @param string     $key
     * @param Collection $items
     */
    private function removeOnes(string $key, Collection $items): void
    {
        $ones = $this->ones($items);
        if ($ones->count()) {
            self::$rds->hDel($key, $ones->keys()->toArray());
        }
    }

    /**
     * 格式化Ones
     * @param Collection $items
     * @param string     $type
     * @return Collection
     */
    private function ones(Collection $items, string $type = 'init'): Collection
    {
        $ones = collect();
        $now  = Carbon::now()->toDateTimeString();
        collect($items)->each(function ($item) use ($now, $ones, $type) {
            $ones->put($item->type . '|' . $item->value, $item->value . '|' . $type . '|' . $now);
        });
        return $ones;
    }
}
