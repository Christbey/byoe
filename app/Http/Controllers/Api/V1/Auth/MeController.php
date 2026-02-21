<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use Illuminate\Http\Request;

/**
 * @group Authentication
 *
 * APIs for user authentication including login, registration, and token management.
 */
class MeController extends Controller
{
    /**
     * Get current user
     *
     * Retrieve the authenticated user's profile information including shop and provider relationships.
     *
     * @authenticated
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "shop@example.com",
     *     "shop": {
     *       "id": 1,
     *       "business_name": "Coffee Shop"
     *     }
     *   }
     * }
     */
    public function __invoke(Request $request): UserResource
    {
        return UserResource::make(
            $request->user()->load(['shop', 'provider'])
        );
    }
}
