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
        Schema::create('pam_role_account', function (Blueprint $table) {
            $table->unsignedMediumInteger('account_id')->default(0)->comment('账户id');
            $table->unsignedMediumInteger('role_id')->default(0)->comment('角色id');

            $table->index('role_id', 'role_id');
            $table->index('account_id', 'account_id');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('pam_role_account');
    }
};
