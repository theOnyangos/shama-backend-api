<?php

namespace App\Services\Api\V1;

use App\Http\Resources\ApiResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

const counties_table = "shama_counties";
const regions_table = "shama_regions";
const streets_table = "shama_streets";

// This function gets a single county name from the database
function getCountyName($countyId)
{
    return DB::table(counties_table)->where('county_code', $countyId)->first()->county_name;
}

// This method gets the region name
function getRegionName($regionId)
{
    return DB::table(regions_table)->where('id', $regionId)->first()->region_name;
}

class AddressService
{
    // Status codes
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_SERVER = 500;

    public static function getKenyanCounties(): JsonResponse
    {
        try {
            $counties = DB::table('shama_counties')->get();
            $message = 'All counties fetched successfully';
            $token = null;
            return ApiResource::successResponse($counties, $message, $token, self::STATUS_CODE_SUCCESS);

        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public static function getRegionsInCounties($request, $countyId): JsonResponse
    {
        try {
            // Get all regions in the db where the county code is same as the one passed
            $regions = DB::table(regions_table)->where('county_code', $countyId)->get();
            // Send back a success message upon successful data fetch.
            $message = "Regions for ". getCountyName($countyId)." fetched successfully";
            $token = null;
            return ApiResource::successResponse($regions, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    public function getStreetsInRegion($request, $regionId): JsonResponse
    {
        try {
            // Get all streets for respective region from the database
            $streets = DB::table(streets_table)->where('region_id', $regionId)->get();
            // Send back success message
            $message = 'Streets for '.getRegionName($regionId).' region fetched successfully';
            $token = null;
            return ApiResource::successResponse($streets, $message, $token, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

}
