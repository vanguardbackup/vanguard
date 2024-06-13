<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remote_servers', function (Blueprint $table) {
            $table->string('connectivity_status')->nullable()->after('port');
        });
    }
};
