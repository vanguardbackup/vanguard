<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_tasks', function (Blueprint $table) {
            $table->dropColumn('isolated_username');
            $table->dropColumn('isolated_password');
        });
    }

    public function down(): void
    {
        Schema::table('backup_tasks', function (Blueprint $table) {
            $table->string('isolated_username')->nullable();
            $table->string('isolated_password')->nullable();
        });
    }
};
