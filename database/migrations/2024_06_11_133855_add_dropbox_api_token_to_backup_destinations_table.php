<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_destinations', function (Blueprint $table) {
            $table->string('dropbox_api_token')->nullable();
        });
    }
};
