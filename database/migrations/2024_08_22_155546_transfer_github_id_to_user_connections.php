<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transfer data from users table to user_connections table
        $users = User::whereNotNull('github_id')->get();

        foreach ($users as $user) {
            $user->connections()->create([
                'provider_name' => 'github',
                'provider_user_id' => $user->github_id,
                // Other fields are left as NULL as we don't have this information
            ]);
        }

        // Remove the github_id column from the users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('github_id');
        });
    }

    public function down(): void
    {
        // Add the github_id column back to the users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('github_id')->nullable();
        });

        // Transfer data back from user_connections to users table
        $connections = DB::table('user_connections')
            ->where('provider_name', 'github')
            ->get();

        foreach ($connections as $connection) {
            User::where('id', $connection->user_id)
                ->update(['github_id' => $connection->provider_user_id]);
        }

        // Remove the github connections from user_connections table
        DB::table('user_connections')->where('provider_name', 'github')->delete();
    }
};
