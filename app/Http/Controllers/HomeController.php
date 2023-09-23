<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Api\V1\HomeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;

class HomeController
{
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
}
