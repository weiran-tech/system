<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePamTokenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pam_token', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('account_id')->default(0)->comment('用户id');
            $table->string('device_id')->default('')->comment('设备id');
            $table->string('device_type')->default('')->comment('设备类型');
            $table->string('login_ip')->default('')->comment('token 登录IP');
            $table->string('token_hash')->default('')->comment('token 的md5值');
            $table->string('push_id')->default('')->comment('推送ID');
            $table->dateTime('expired_at')->comment('过期时间');

            $table->index(['account_id', 'device_type'], 'k_user_device');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pam_token');
    }
}
