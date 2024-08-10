<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles device authentication and token generation.
 *
 * This controller is responsible for validating user credentials
 * and creating a new Sanctum token for authenticated devices.
 * It will only function if the device authentication endpoint is enabled in the configuration.
 */
class AuthenticateDeviceController extends Controller
{
    /**
     * Authenticate the device and generate a Sanctum token.
     *
     * @throws ValidationException
     * @throws NotFoundHttpException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $this->checkEndpointAvailability();

        $credentials = $this->validateRequest($request);

        $user = $this->getUserByEmail($credentials['email']);

        if (! $user instanceof User || ! $this->checkPassword($user, $credentials['password'])) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($credentials['device_name'])->plainTextToken;

        return response()->json(['token' => $token]);
    }

    /**
     * Check if the device authentication endpoint is enabled.
     *
     * @throws NotFoundHttpException
     */
    private function checkEndpointAvailability(): void
    {
        if (! config('app.enable_device_authentication_endpoint')) {
            throw new NotFoundHttpException('Device authentication endpoint is not available.');
        }
    }

    /**
     * Validate the incoming request.
     *
     * @return array{email: string, password: string, device_name: string}
     */
    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255', 'regex:/^[\w\-\s]+$/'],
        ]);
    }

    /**
     * Get the user by email.
     */
    private function getUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Check if the provided password is correct.
     */
    private function checkPassword(User $user, string $password): bool
    {
        return Hash::check($password, (string) $user->getAttribute('password'));
    }
}
