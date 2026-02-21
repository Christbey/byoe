<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * APIs for user authentication including login, registration, and token management.
 */
class LoginController extends Controller
{
    /**
     * Login
     *
     * Authenticate a user and return an access token for API requests.
     *
     * @bodyParam email string required The user's email address. Example: shop@example.com
     * @bodyParam password string required The user's password. Example: password123
     * @bodyParam device_name string Optional device name for the token. Example: iPhone 14
     *
     * @response 200 {
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "shop@example.com"
     *   },
     *   "token": "1|abc123...",
     *   "expires_at": "2026-03-22T10:00:00.000000Z"
     * }
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var User $user */
        $user = $request->user();

        $token = $user->createToken(
            name: $request->device_name ?? 'api-token',
            abilities: $this->getTokenAbilities($user)
        );

        return response()->json([
            'user' => UserResource::make($user),
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    /**
     * Get token abilities based on user roles.
     *
     * @return array<string>
     */
    private function getTokenAbilities(User $user): array
    {
        return match (true) {
            $user->hasRole('admin') => ['*'],
            $user->hasRole('shop_owner') => ['shop:manage', 'bookings:view', 'payments:create'],
            $user->hasRole('provider') => ['provider:accept', 'bookings:manage'],
            default => [],
        };
    }
}
