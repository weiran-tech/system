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
        Schema::create('pam_permission_role', function (Blueprint $table) {
            $table->unsignedInteger('permission_id')->default(0);
            $table->unsignedInteger('role_id')->default(0);

            $table->primary(['permission_id', 'role_id']);


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('pam_permission_role');
    }
};
