<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_task_logs', function (Blueprint $table) {
            $table->timestamp('successful_at')->nullable()->after('output');
        });
    }
};
