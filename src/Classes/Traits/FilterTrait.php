<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Weiran\Framework\Classes\Resp;
use Weiran\Framework\Http\Pagination\PageInfo;
use Response;

/**
 * 对 filter 的封装, 进行页面返回
 */
trait FilterTrait
{

    /**
     * @param Model           $Db 数据对象
     * @param string|\Closure $resource 资源
     * @param array           $append 增加
     * @return JsonResponse
     */
    public static function paginationInfo($Db, $resource, $append = [])
    {
        $pageInfo = new PageInfo(input());
        /* 缓存查询结果数量, 暂不开启
         --------------------------------------------
        if ($cache) {
            $binding = $this->getBindings();
            array_unshift($binding, str_replace('?', '%s', $this->toSql()));
            $sql      = call_user_func_array('sprintf', $binding);
            $cacheKey = md5($sql);
        }
        */

        $total = (clone $Db)->count();
        $page  = $pageInfo->page();
        $size  = $pageInfo->size();
        $pages = (int) ceil($total / $size);

        /** @var Collection $list */
        $list = $Db->pageFilter($pageInfo)->get();
        $data = collect();
        if ($list->count()) {
            if (is_string($resource) && class_exists($resource)) {
                $list->each(function ($item) use ($resource, $data) {
                    $res = (new $resource($item))->toArray(app('request'));
                    $data->push($res);
                });
            }
            if (is_callable($resource)) {
                $list->each(function ($payload) use ($resource, $data) {
                    $res = $resource(...array_values(func_get_args()));
                    $data->push($res);
                });
            }
        }
        $return = [
            'status'  => Resp::SUCCESS,
            'message' => '获取列表成功',
            'data'    => [
                'list'       => $data->toArray(),
                'pagination' => [
                    'total' => $total,
                    'page'  => $page,
                    'size'  => $size,
                    'pages' => $pages,
                ],
            ],
        ];

        // 附加数据
        if ($append) {
            $return['data'] += $append;
        }

        return Response::json($return, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param Builder  $query 查询条件
     * @param PageInfo $pageInfo 分页
     * @return mixed
     */
    public function scopePageFilter($query, PageInfo $pageInfo)
    {
        $offset = ($pageInfo->page() - 1) * $pageInfo->size();

        return $query->offset($offset)->take($pageInfo->size());
    }
}