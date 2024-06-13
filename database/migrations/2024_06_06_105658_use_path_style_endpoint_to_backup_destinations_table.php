<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_destinations', function (Blueprint $table) {
            $table->boolean('path_style_endpoint')->default(false)->after('s3_secret_key');
        });
    }
};
