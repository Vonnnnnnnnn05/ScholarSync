<?php

namespace App\Http\Controllers;

use App\Services\NavigationNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationCountController extends Controller
{
    public function __invoke(Request $request, NavigationNotificationService $service): JsonResponse
    {
        return response()->json($service->getCounts($request->user()));
    }
}
