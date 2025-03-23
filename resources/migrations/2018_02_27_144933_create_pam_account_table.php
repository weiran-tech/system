<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('pam_account', function (Blueprint $table) {
            $table->increments('id')->comment('用户id');
            $table->string('username', 45)->default('')->comment('用户名');
            $table->string('mobile', 45)->default('');
            $table->string('email', 50)->default('')->comment('邮箱');
            $table->integer('parent_id')->default(0)->comment('父账号ID');
            $table->string('password', 45)->default('')->comment('密码');
            $table->string('password_key', 10)->default('')->comment('密码Key');
            $table->string('type', 20)->default('');
            $table->tinyInteger('is_enable')->default(1)->comment('是否禁用');
            $table->string('disable_reason', 255)->default('')->comment('禁用原因');
            $table->dateTime('disable_start_at')->nullable()->comment('禁用开始时间');
            $table->dateTime('disable_end_at')->nullable()->comment('禁用结束时间');
            $table->integer('login_times')->default(0)->comment('登录次数');
            $table->string('login_ip', 20)->default('')->comment('注册IP');
            $table->string('reg_ip', 20)->default('')->comment('注册IP');
            $table->string('reg_platform', 15)->default('')->comment('注册平台');
            $table->string('remember_token', 250)->default('')->comment('token');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('logined_at')->nullable()->comment('上次登录时间');
            $table->dateTime('updated_at')->nullable()->comment('修改时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('pam_account');
    }
};
