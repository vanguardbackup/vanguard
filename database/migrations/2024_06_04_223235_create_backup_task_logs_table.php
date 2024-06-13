<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_task_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_task_id')->constrained()->cascadeOnDelete();
            $table->text('output');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_task_logs');
    }
};
