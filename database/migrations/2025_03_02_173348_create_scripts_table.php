<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scripts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->string('label');
            $table->string('type')->comment('prescript or postscript');
            $table->text('script');
            $table->timestamps();
        });

        Schema::create('backup_task_script', function (Blueprint $table) {
            $table->foreignId('backup_task_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('script_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Create a composite primary key
            $table->primary(['backup_task_id', 'script_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_task_script');
        Schema::dropIfExists('scripts');
    }
};
