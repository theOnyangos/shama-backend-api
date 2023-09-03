<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    protected AddressService $addressService;

    // The constructor function
    public function __construct(AddressService $addressService)
    {
        $this->addressService = $addressService;
    }

    // This method gets all the counties (KE)
    public function getAllCounties(Request $request): JsonResponse
    {
        return $this->addressService->getKenyanCounties();
    }

    // This method gets all regions in counties (KE)
    public function getAllRegionsInCounties(Request $request, $countyId): JsonResponse
    {
        return $this->addressService->getRegionsInCounties($request, $countyId);
    }

    // This method gets all region streets in kenya
    public function getAllStreetsInRegion(Request $request, $regionId): JsonResponse
    {
        return $this->addressService->getStreetsInRegion($request, $regionId);
    }
}
