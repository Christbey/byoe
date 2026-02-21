<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Authentication
 *
 * APIs for user authentication including login, registration, and token management.
 */
class LogoutController extends Controller
{
    /**
     * Logout
     *
     * Revoke the current access token and log the user out.
     *
     * @authenticated
     *
     * @response 200 {
     *   "message": "Successfully logged out"
     * }
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }
}
