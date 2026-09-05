<?php

namespace App\Http\Controllers;

use App\Services\VinylHubStatusOperationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VinylHubStatusOperationController extends Controller
{
    public function create(Request $request, VinylHubStatusOperationService $service): JsonResponse
    {
        $identity = $request->validate([
            'external_subject' => ['required', 'string', 'max:255'],
            'operation_key' => ['required', 'string', 'max:255'],
        ]);

        return response()->json($service->create(
            $identity['external_subject'],
            $identity['operation_key'],
            $request->except(['external_subject', 'operation_key']),
        ));
    }

    public function read(Request $request, VinylHubStatusOperationService $service): JsonResponse
    {
        $data = $request->validate([
            'external_subject' => ['required', 'string', 'max:255'],
            'operation_key' => ['required', 'string', 'max:255'],
            'repair' => ['nullable', 'boolean'],
        ]);

        return response()->json($service->read(
            $data['external_subject'],
            $data['operation_key'],
            $data['repair'] ?? true,
        ));
    }
}
