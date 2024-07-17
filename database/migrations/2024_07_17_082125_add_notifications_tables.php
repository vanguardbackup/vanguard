<?php

use App\Models\BackupTask;
use App\Models\NotificationStream;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_streams', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('type');
            $table->string('value')->nullable()->comment('The type of notification stream, if its email it will be the email address etc.');
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('backup_task_notification_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(BackupTask::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(NotificationStream::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('backup_tasks', function (Blueprint $table) {
            $table->dropColumn('notify_email');
            $table->dropColumn('notify_slack_webhook');
            $table->dropColumn('notify_discord_webhook');

        });
    }
};
