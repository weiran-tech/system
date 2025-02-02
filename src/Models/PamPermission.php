<?php

declare(strict_types = 1);

namespace Weiran\System\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Weiran\Core\Rbac\Contracts\RbacPermissionContract;
use Weiran\Core\Rbac\Traits\RbacPermissionTrait;
use Weiran\Framework\Database\Eloquent\LegacySerializeData;

/**
 * 用户权限
 * @property int                       $id
 * @property string                    $name
 * @property string                    $title
 * @property string                    $description
 * @property string                    $group
 * @property string                    $root
 * @property string                    $module
 * @property string                    $type
 * @property-read Collection|PamRole[] $roles
 * @method static Builder|PamPermission newModelQuery()
 * @method static Builder|PamPermission newQuery()
 * @method static Builder|PamPermission query()
 * @mixin Eloquent
 */
class PamPermission extends Model implements RbacPermissionContract
{
    use RbacPermissionTrait, LegacySerializeData;

    public $timestamps = false;

    protected $table = 'pam_permission';

    protected $fillable = [
        'name',
        'title',
        'description',
        'group',
        'root',
        'module',
        'type',
    ];
}