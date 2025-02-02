<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPamAccountTableAddUemIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pam_account', function (Blueprint $table) {
            $table->index('username', 'k_username');
            $table->index('email', 'k_email');
            $table->index('mobile', 'k_mobile');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pam_account', function (Blueprint $table) {
            $table->dropIndex(['k_username', 'k_email', 'k_mobile']);
        });
    }
}
