<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remote_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('ip_address');
            $table->string('username');
            $table->string('port')->default('22');
            $table->dateTime('last_connected_at')->nullable();
            $table->timestamps();
        });
    }
};
