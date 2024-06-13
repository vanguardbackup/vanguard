<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('type');
            $table->string('s3_access_key')->nullable();
            $table->string('s3_secret_key')->nullable();
            $table->string('s3_bucket_name')->nullable();
            $table->string('custom_s3_region')->nullable();
            $table->string('custom_s3_endpoint')->nullable();
            $table->timestamps();
        });
    }
};
