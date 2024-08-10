<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade');
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }
};
