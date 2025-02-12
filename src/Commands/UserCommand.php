<?php

declare(strict_types = 1);

namespace Weiran\System\Commands;

use Illuminate\Console\Command;
use Weiran\System\Action\Ban;
use Weiran\System\Action\Pam;
use Weiran\System\Action\Sso;
use Weiran\System\Models\PamAccount;
use Weiran\System\Models\PamPermission;
use Weiran\System\Models\PamRole;
use Weiran\System\Models\SysConfig;
use Throwable;

/**
 * User
 */
class UserCommand extends Command
{
    /**
     * 前端部署.
     * @var string
     */
    protected $signature = 'py-system:user 
		{do : actions}
		{--account= : Account Name}
		{--pwd= : Account password}
		{--perm= : The perm need check}
		';

    /**
     * 描述
     * @var string
     */
    protected $description = 'user handler.';

    /**
     * Execute the console command.
     * @return void
     * @throws Throwable
     */
    public function handle()
    {
        $do = $this->argument('do');
        switch ($do) {
            case 'reset_pwd':
                $passport = $this->ask('Your passport?');

                if ($pam = PamAccount::passport($passport)) {
                    $pwd = trim($this->ask('Your aim password'));
                    $Pam = new Pam();
                    if (!$Pam->setPassword($pam, $pwd)) {
                        $this->error($Pam->getError());
                    }
                    else {
                        $this->info('Reset user password success');
                    }
                }
                else {
                    $this->error('Your account not exists');
                }
                break;
            case 'create_user':
                $passport = $this->ask('Please input passport!');
                $password = $this->ask('Please input password!');
                $role     = $this->ask('Please input role name!');
                if (!PamAccount::passport($passport)) {
                    $Pam = new Pam();
                    if ($Pam->register($passport, $password, $role)) {
                        $this->info('User ' . $passport . ' created');
                    }
                    else {
                        $this->error($Pam->getError());
                    }
                }
                else {
                    $this->error('user ' . $passport . ' exists');
                }
                break;
            case 'auto_fill':
                $user = PamAccount::where('type', PamAccount::TYPE_BACKEND)->pluck('id', 'username');
                if (!$user) {
                    return;
                }
                collect($user)->map(function ($id) {
                    PamAccount::where('id', $id)->update([
                        'mobile' => PamAccount::dftMobile($id),
                    ]);
                });
                $this->info(sys_gen_mk(self::class, 'Fill Mobile Over'));
                break;
            case 'clear_expired':
                // 清理已经过期的数据
                $num = (new Sso())->clearExpired();
                $this->info(sys_gen_mk(self::class, 'Delete Expired Token, Num : ' . $num));
                break;
            case 'init_role':
                $roles = [
                    [
                        'name'      => PamRole::FE_USER,
                        'title'     => '用户',
                        'type'      => PamAccount::TYPE_USER,
                        'is_system' => SysConfig::YES,
                    ],
                    [
                        'name'      => PamRole::BE_ROOT,
                        'title'     => '超级管理员',
                        'type'      => PamAccount::TYPE_BACKEND,
                        'is_system' => SysConfig::YES,
                    ],
                ];
                foreach ($roles as $role) {
                    if (!PamRole::where('name', $role['name'])->exists()) {
                        PamRole::create($role);
                    }
                }
                $this->info('Init Role success');
                break;
            case 'auto_enable':
                if (!sys_setting('wr-system::pam.auto_enable')) {
                    $this->info(sys_gen_mk(self::class, 'auto enable disabled!'));
                    return;
                }
                (new Pam())->autoEnable();
                $this->info(sys_gen_mk(self::class, 'auto enable pam!'));
                break;
            case 'user':
                $role     = $this->ask('Which <role> you want assign to ?');
                $passport = $this->ask('Which <passport> you want to assign?');
                $this->user($role, $passport);
                break;
            case 'assign':
                $name = $this->ask('Which role you want assign permission ?');
                $type = $this->ask('Which permission list <user type> you want to get ?');
                $this->assign($name, $type);
                break;
            case 'clear_log':
                (new Pam())->clearLog();
                $this->info(sys_gen_mk(self::class, 'auto clear log!'));
                break;
            case 'ban_init':
                (new Ban())->initCache();
                $this->info(sys_gen_mk(self::class, 'Init Ban Cache!'));
                break;
            case 'check_perm':
                $permission = $this->option('perm');
                $this->checkPermission($permission);
                break;
            default:
                $this->error('Please type right action![reset_pwd, init_role, create_user, clear_expired, ban_init, auto_enable, clear_log, auto_fill]');
                break;
        }
    }


    /**
     * 将权限赋值给指定的用户组
     */
    private function assign($name, $type)
    {
        /** @var PamRole $role */
        $role = PamRole::where('name', $name)->first();

        if (!$role) {
            $this->error(
                sys_gen_mk(self::class, 'Role [' . $name . '] not exists in table !')
            );

            return;
        }

        $permissions = (new PamPermission())::where('type', $type)->get();
        if (!$permissions) {
            $this->error(sys_gen_mk(self::class, 'Permission type [' . $type . '] has no permissions !'));
            return;
        }
        $role->syncPermission($permissions);
        $this->info(sys_gen_mk(self::class, "Save [{$type}] permission to role [{$name}] !"));
    }


    /**
     * 将角色赋值给指定的用户
     */
    private function user($role, $passport)
    {
        /** @var PamRole $role */
        $role = PamRole::where('name', $role)->first();

        if (!$role) {
            $this->error(sys_gen_mk(self::class, 'Role [' . $role . '] not exists in table !'));
            return;
        }

        $pam = PamAccount::passport($passport);
        if (!$pam) {
            $this->error(sys_gen_mk(self::class, 'No such pam account !'));
            return;
        }
        $pam->attachRole($role);
        $this->info(sys_gen_mk(self::class, "Save [{$role->id}, {$role->type}] role to account [{$passport}] !"));
    }


    /**
     * @param string $permission 需要检测的权限
     */
    private function checkPermission(string $permission)
    {
        if (PamPermission::where('name', $permission)->exists()) {
            $this->info(
                sys_gen_mk(self::class, 'Permission `' . $permission . '` in table ')
            );
        }
        else {
            $this->error(
                sys_gen_mk(self::class, 'Permission `' . $permission . '` not in table')
            );
        }
    }
}