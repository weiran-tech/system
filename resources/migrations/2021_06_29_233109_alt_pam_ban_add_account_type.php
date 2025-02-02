<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AltPamBanAddAccountType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pam_ban', function (Blueprint $table) {
            $table->string('account_type', 20)->default('user')->comment('账号类型')->after('id');
            $table->unsignedBigInteger('ip_start')->default(0)->comment('起始IP')->after('value');
            $table->unsignedBigInteger('ip_end')->default(0)->comment('结束IP')->after('ip_start');
            $table->string('note', 255)->default('')->comment('备注')->after('ip_end');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pam_ban', function (Blueprint $table) {
            $table->dropColumn(['account_type', 'ip_start', 'ip_end', 'note']);
        });
    }
}
