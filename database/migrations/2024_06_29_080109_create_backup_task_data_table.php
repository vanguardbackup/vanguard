<?php

use App\Models\BackupTask;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_task_data', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(BackupTask::class)->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('size')->comment('The size of the backup data in bytes, whether it is DB or files etc.')->nullable();
            $table->unsignedInteger('duration')->comment('The time it took for the backup task to run in seconds.')->nullable();
            $table->timestamps();
        });
    }
};
