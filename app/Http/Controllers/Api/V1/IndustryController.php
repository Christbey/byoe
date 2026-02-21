<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\IndustryResource;
use App\Models\Industry;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Industries
 *
 * APIs for retrieving industry data including skills and service request templates.
 */
class IndustryController extends Controller
{
    /**
     * List industries
     *
     * Returns all active industries sorted by sort order and name.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Food & Beverage",
     *       "description": "Restaurants, cafes, and food service"
     *     }
     *   ]
     * }
     */
    public function index(): AnonymousResourceCollection
    {
        $industries = Industry::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return IndustryResource::collection($industries);
    }

    /**
     * Get industry details
     *
     * Retrieve a specific industry including its associated skills and service request templates.
     *
     * @urlParam industry integer required The industry ID. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "Food & Beverage",
     *     "skills": [
     *       {"id": 1, "name": "Barista"}
     *     ],
     *     "templates": [
     *       {"id": 1, "title": "Morning Shift Coverage"}
     *     ]
     *   }
     * }
     */
    public function show(int|string $industry): IndustryResource
    {
        // Manually resolve industry
        $industry = Industry::findOrFail($industry);

        $industry->load(['skills', 'templates']);

        return IndustryResource::make($industry);
    }
}
