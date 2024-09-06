<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('dismissable_type');
            $table->string('dismissable_id');
            $table->timestamp('dismissed_at');
            $table->timestamps();

            $table->unique(['user_id', 'dismissable_type', 'dismissable_id']);
            $table->index(['dismissable_type', 'dismissable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dismissals');
    }
};
