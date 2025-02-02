<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPamAccountTableAddNoteField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pam_account', function (Blueprint $table) {
            $table->string('note', 100)->after('reg_platform')->default('')->comment('备注');
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
            $table->dropColumn(['note']);
        });
    }
}
