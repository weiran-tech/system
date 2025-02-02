<?php

declare(strict_types = 1);

namespace Weiran\System\Classes\Auth\Provider;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider as UserProviderBase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Weiran\System\Classes\Contracts\PasswordContract;
use Weiran\System\Models\PamAccount;

/**
 * 用户认证
 */
class PamProvider implements UserProviderBase
{
    /**
     * The Eloquent user model.
     * @var string
     */
    protected $model;

    /**
     * Create a new database user provider.
     * @param string $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Retrieve a user by their unique identifier.
     * @param mixed $identifier ID
     * @return Builder|Builder[]|Collection|Model
     */
    public function retrieveById($identifier)
    {
        return $this->createModel()->newQuery()->find($identifier);
    }

    /**
     * Retrieve a user by the given credentials.
     * DO NOT TEST PASSWORD HERE!
     * @param array $credentials 凭证
     * @return Builder|Model
     */
    public function retrieveByCredentials(array $credentials)
    {
        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
        $query = $this->createModel()->newQuery();

        foreach ($credentials as $key => $value) {
            if (!Str::contains($key, ['password', 'pwd_type'])) {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    /**
     * @param Authenticatable|PamAccount $user        用户
     * @param array                      $credentials 凭证
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $password = $credentials['password'];
        $pwdType  = $credentials['pwd_type'] ?? 'plain';

        $Pwd = app(PasswordContract::class);
        return $Pwd->check($user, $password, $pwdType);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     * @param mixed  $identifier ID
     * @param string $token      Token
     * @return Builder|Model
     */
    public function retrieveByToken($identifier, $token)
    {
        $model = $this->createModel();

        return $model->newQuery()
            ->where($model->getKeyName(), $identifier)
            ->where($model->getRememberTokenName(), $token)
            ->first();
    }

    /**
     * 更新记住的token
     * @param Authenticatable|PamAccount $user  用户
     * @param string                     $token Token
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->logined_at = Carbon::now();
        $user->save();
    }

    /**
     * Create a new instance of the model.
     * @return Model|PamAccount
     */
    public function createModel()
    {
        $class = '\\' . ltrim($this->model, '\\');

        return new $class();
    }
}