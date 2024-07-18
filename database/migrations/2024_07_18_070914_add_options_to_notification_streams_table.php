<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_streams', function (Blueprint $table) {
            $table->dateTime('receive_successful_backup_notifications')->default(now())->nullable();
            $table->dateTime('receive_failed_backup_notifications')->default(now())->nullable();
        });
    }
};
