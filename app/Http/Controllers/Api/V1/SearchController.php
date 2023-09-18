<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private SearchService $searchService;

    // This is the constructor function
    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    // This function handles search functionality in the admin section of the app
    public function getUserSearchData(Request $request): JsonResponse
    {
        return $this->searchService->searchUsers($request);
    }
}
