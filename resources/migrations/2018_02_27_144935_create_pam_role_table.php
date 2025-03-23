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
        Schema::create('pam_role', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->string('name', 100)->default('')->comment('角色组名称');
            $table->string('title', 100)->default('')->comment('中文名称');
            $table->string('description', 100)->default('');
            $table->string('type', 20)->default('')->comment('账户类型');
            $table->tinyInteger('is_enable')->default(1)->comment('是否可用');
            $table->tinyInteger('is_system')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('pam_role');
    }
};
