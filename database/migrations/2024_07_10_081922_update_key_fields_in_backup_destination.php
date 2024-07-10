<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_destinations', function (Blueprint $table) {
            $table->longText('s3_access_key')->nullable()->change();
            $table->longText('s3_secret_key')->nullable()->change();
            $table->longText('s3_bucket_name')->nullable()->change();
            $table->longText('custom_s3_endpoint')->nullable()->change();
        });
    }
};
