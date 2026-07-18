<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => config('features.maintenance') ? 'maintenance' : 'ok',
            'maintenance' => (bool) config('features.maintenance'),
            'payments_maintenance' => (bool) config('features.payments_maintenance'),
        ]);
    }
}
