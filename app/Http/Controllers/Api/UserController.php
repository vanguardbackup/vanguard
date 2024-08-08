<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles API requests for user information.
 *
 * This controller is responsible for returning the authenticated user's
 * information as a JSON resource. It's typically used for retrieving
 * the current user's profile data in API requests.
 */
class UserController extends Controller
{
    /**
     * Handle the incoming request to retrieve user information.
     *
     * This method returns a UserResource instance containing the authenticated
     * user's information. It's invoked when a GET request is made to the
     * associated route.
     *
     * @param  Request  $request  The incoming HTTP request instance.
     * @return JsonResponse A JSON response containing the user resource.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'The requested operation requires a valid authentication token.'], 401);
        }

        return (new UserResource($user))->response();
    }
}
