<?php

use App\Models\BackupDestination;
use App\Models\RemoteServer;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('source_path');
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(BackupDestination::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(RemoteServer::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('status')->default('ready');
            $table->enum('frequency', ['daily', 'weekly'])->default('daily');
            $table->string('cron_run_at')->nullable()->comment('Cron expression for scheduling the backup task');
            $table->dateTime('last_run_at')->nullable();
            $table->timestamps();
        });
    }
};
