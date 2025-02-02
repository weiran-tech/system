<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AltSysConfigTableAddKeyIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('sys_config', function (Blueprint $table) {
            $table->index(['namespace', 'group', 'item'], 'k_key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('sys_config', function (Blueprint $table) {
            $table->dropIndex('k_key');
        });
    }
}
