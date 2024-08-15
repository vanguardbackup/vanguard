<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_two_factor_at')->nullable();
            $table->string('last_two_factor_ip', 45)->nullable();
            $table->string('two_factor_verified_token', 100)->nullable();
        });
    }
};
