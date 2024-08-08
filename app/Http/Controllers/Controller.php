<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Base controller class for the application.
 * Serves as a foundation for other controllers to extend from.
 */
abstract class Controller
{
    /**
     * Authorize the request based on the given ability.
     *
     * @param  Request  $request  The incoming request
     * @param  string  $ability  The ability to check
     * @return JsonResponse|null Returns null if authorized, otherwise returns a JSON response
     */
    protected function authorizeRequest(Request $request, string $ability): ?JsonResponse
    {
        $user = $request->user();

        Log::debug('Authorizing request', ['user_id' => $user ? $user->id : null, 'ability' => $ability]);

        if (! $user) {
            Log::warning('No authenticated user found');

            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'No authenticated user found.',
            ], 401);
        }

        if (! $user->tokenCan($ability)) {
            Log::warning('User does not have required ability', ['user_id' => $user->id, 'required' => $ability]);

            return response()->json([
                'error' => 'Unauthorized',
                'message' => "User does not have the required ability: {$ability}",
            ], 403);
        }

        Log::debug('Authorization successful', ['user_id' => $user->id, 'ability' => $ability]);

        return null; // Authorization successful
    }
}
