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
        Schema::create('pam_permission', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->default('');
            $table->string('title', 255)->default('');
            $table->string('description', 255)->default('');
            $table->string('group', 50)->default('');
            $table->string('root', 50)->default('');
            $table->string('module', 50)->default('');
            $table->string('type', 50)->default('');

            $table->unique('name', 'u_permission_name');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('pam_permission');
    }
};
