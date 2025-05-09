<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_tasks', function (Blueprint $table) {
            $table->string('webhook_token', 64)->nullable();
        });
    }
};
