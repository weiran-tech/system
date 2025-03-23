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
        Schema::create('sys_config', function (Blueprint $table) {
            $table->increments('id')->comment('配置id');
            $table->char('namespace', 50)->comment('命名空间');
            $table->string('group', 50)->default('')->comment('配置分组');
            $table->string('item', 50)->default('')->comment('配置名称');
            $table->text('value')->comment('配置值');
            $table->string('description', 255)->default('')->comment('配置介绍');

            $table->index('item', 'conf_name');
            $table->index('group', 'conf_group');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_config');
    }
};
