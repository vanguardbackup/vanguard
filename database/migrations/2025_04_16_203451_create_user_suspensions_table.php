<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->dateTime('suspended_at')->nullable();
            $table->dateTime('suspended_until')->nullable();
            $table->dateTime('lifted_at')->nullable();
            $table->foreignId('lifted_by_admin_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('suspended_reason')->nullable();
            $table->text('private_note')->nullable();
            $table->text('unsuspension_note')->nullable();
            $table->dateTime('notify_user_upon_suspension_being_lifted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_suspensions');
    }
};
