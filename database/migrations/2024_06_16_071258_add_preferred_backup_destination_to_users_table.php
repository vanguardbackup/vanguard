<?php

use App\Models\BackupDestination;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(BackupDestination::class, 'preferred_backup_destination_id')
                ->nullable()
                ->constrained('backup_destinations')
                ->nullOnDelete();
        });
    }
};
