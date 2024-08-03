<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_streams', function (Blueprint $table) {
            $table->text('additional_field_one')->nullable();
            $table->text('additional_field_two')->nullable();
        });
    }
};
