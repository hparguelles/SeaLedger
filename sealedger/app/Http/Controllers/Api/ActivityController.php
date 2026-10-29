<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    public function clear(): JsonResponse
    {
        Activity::query()->delete();

        return response()->json(['message' => 'History cleared.']);
    }
}
