<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePamBanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pam_ban', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type')->default('')->comment('类型');
            $table->string('value')->default('')->comment('值');

            $table->index('value', 'k_val');

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
        Schema::dropIfExists('pam_ban');
    }
}
