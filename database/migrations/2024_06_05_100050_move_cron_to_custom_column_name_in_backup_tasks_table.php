<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_tasks', function (Blueprint $table) {
            $table->renameColumn('cron_run_at', 'custom_cron_expression');
            $table->string('time_to_run_at')->after('status')->nullable();
            $table->string('frequency')->change()->nullable();
        });
    }
};
