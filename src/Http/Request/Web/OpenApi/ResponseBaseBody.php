<?php

declare(strict_types = 1);

namespace Weiran\System\Http\Request\Web\OpenApi;

use OpenApi\Attributes as OA;
use Weiran\System\Http\OpenApi\BaseResponseBody;

/**
 * OpenApi 相应基础体
 * @deprecated 1.0
 *
 * @see        BaseResponseBody
 */
#[OA\Schema()]
abstract class ResponseBaseBody extends BaseResponseBody {}
