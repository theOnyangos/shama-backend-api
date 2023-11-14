<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Services\Api\V1\HomeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;


class HomeController
{
    const STATUS_CODE_SUCCESS = 200;
    const STATUS_CODE_SUCCESS_CREATE = 201;
    const STATUS_CODE_ERROR = 422;
    const STATUS_CODE_FORBIDDEN = 403;
    const STATUS_CODE_NOT_FOUND = 404;
    const STATUS_CODE_SERVER = 500;

    public HomeService $homeService;

    public function __construct(HomeService $homeService)
    {
        $this->homeService = $homeService;
    }

    public function storeAccountClosureReason(Request $request): JsonResponse
    {
        return $this->homeService->saveAccountClosureReason($request);
    }

    // This function opens closure form
    public function openCloseAccountPage(Request $request, $userId): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application
    {
        $user_id = Crypt::decryptString($userId);
        $user = User::find($user_id);

        $userName = $user->first_name." ".$user->last_name;
        return view('delete', ['userId' => $user_id, 'name' => $userName]);
    }

    // Handle create category
    public function createNewCategory(Request $request, $userId): JsonResponse
    {
        try {
            $validation = Validator::make($request->all(), [
                'title' => ['required']
            ]);

            // Validate Form Fields
            if ($validation->fails()) {
                $message = 'One or more inputs have errors, please check that all required inputs are filled and try again.';
                return ApiResource::validationErrorResponse($validation->errors(), $message, self::STATUS_CODE_ERROR);
            }

            $categories = Category::create(
                [
                    'title' => $request->get('title'),
                ]
            );

            $userName = ActivityHelper::getUserName($userId);
            ActivityHelper::logActivity($userId, $userName." created a category: ". $request->get('title'));

            // Return success response
            $message = 'Category created successfully.';
            $token = null;
            return ApiResource::successResponse($categories, $message, $token, self::STATUS_CODE_SUCCESS);

        // Trow System error if any
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function gets all categories from the database
    public function getAllCategories(): JsonResponse
    {
        try {
            $categories = Category::all();

            // Return success response
            $message = 'Category fetched successfully.';
            $token = null;
            return ApiResource::successResponse($categories, $message, $token, self::STATUS_CODE_SUCCESS);

            // Trow System error if any
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function gets players by their category
    // Added a category_id field in the users table
    public function getPlayersByCategory(Request $request, $categoryId): JsonResponse
    {
        try {
            $categoryUsers = User::where('category_id', $categoryId)
                ->select('id', 'first_name', 'last_name')
                ->get();

            // Return success response
            $message = 'Category players fetched successfully.';
            $token = null;
            return ApiResource::successResponse($categoryUsers, $message, $token, self::STATUS_CODE_SUCCESS);

            // Trow System error if any
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function uploads player document
    // Created player document table
    public function uploadPlayerDocuments(Request $request, $userId, $playerId): JsonResponse
    {
        try {
            // Find the user with ID
            $user = User::find($playerId);
            // Check if user with ID provided exists
            if (!$user) return ApiResource::errorResponse('User not found', self::STATUS_CODE_NOT_FOUND);

            $message = "";
            $fullPath = "";
            $playerName = ActivityHelper::getUserName($playerId);
            $filename = "";
            $userName = ActivityHelper::getUserName($userId);

            $request->validate([
                'file' => 'required|file|mimes:pdf'
            ]);

            if ($request->hasFile('file')) {

                $file = $request->file('file');
                $filenameWithExt = $file->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);


                $extension = $file->getClientOriginalExtension();
                $fileNameToStore = str_replace(" ", "_", $filename) . '-' . time() . '.' . $extension;
                $path = 'assets/documents/' . $fileNameToStore;
                $fullPath = url($path);

                $file->move(public_path('assets/documents'), $fileNameToStore);
                // Additional processing or saving of file information
                $message = 'File uploaded successfully to: ' . $fullPath;
                // Update image in the database
                $userDocument = new Document();
                $userDocument->user_id = $playerId ?: $userDocument->user_id;
                $userDocument->document_path = $fullPath ?: $userDocument->null;
                $userDocument->save();
            } else {
                $message = 'No file was uploaded.';
            }

            ActivityHelper::logActivity($userId, $userName." uploaded a document: ". $filename. " for player: ".$playerName);
            return ApiResource::successResponse($userDocument, $message, null, self::STATUS_CODE_SUCCESS);
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }

    // This function gets all users activities from the database
    public function getUsersActivities(Request $request): JsonResponse
    {
        try {
            $activities = Activity::all();
            // Return success response
            $message = 'Users activities fetched successfully.';
            $token = null;
            return ApiResource::successResponse($activities, $message, $token, self::STATUS_CODE_SUCCESS);

            // Trow System error if any
        } catch (\Throwable $err) {
            $message = $err->getMessage();
            return ApiResource::validationErrorResponse('System Error!', $message, self::STATUS_CODE_SERVER);
        }
    }
}
