<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @group Authentication
 *
 * APIs for user authentication including login, registration, and token management.
 */
class RegisterController extends Controller
{
    /**
     * Register
     *
     * Create a new user account and return an access token.
     *
     * @bodyParam name string required The user's full name. Example: John Doe
     * @bodyParam email string required The user's email address. Example: newshop@example.com
     * @bodyParam password string required The user's password (min 8 characters). Example: password123
     * @bodyParam password_confirmation string required Password confirmation. Example: password123
     * @bodyParam role string required User role (shop_owner or provider). Example: shop_owner
     * @bodyParam device_name string Optional device name for the token. Example: iPhone 14
     *
     * @response 201 {
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "newshop@example.com"
     *   },
     *   "token": "1|abc123...",
     *   "expires_at": "2026-03-22T10:00:00.000000Z"
     * }
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'terms_accepted_at' => now(),
            ]);

            // Assign the requested role
            $user->assignRole($request->role);

            return $user;
        });

        $token = $user->createToken(
            name: $request->device_name ?? 'api-token',
            abilities: $this->getTokenAbilities($user)
        );

        return response()->json([
            'user' => UserResource::make($user),
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ], 201);
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
