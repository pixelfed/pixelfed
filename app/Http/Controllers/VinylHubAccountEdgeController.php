<?php

namespace App\Http\Controllers;

use App\Services\VinylHubAccountEdgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VinylHubAccountEdgeController extends Controller
{
    public function provision(Request $request, VinylHubAccountEdgeService $service): JsonResponse
    {
        $data = $request->validate([
            'external_subject' => ['required', 'string', 'max:255'],
            'technical_handle' => ['required', 'string', 'regex:/^vh[a-z0-9]+$/', 'min:3', 'max:30'],
            'technical_email' => ['required', 'string', 'email:strict', 'max:255'],
            'display_seed' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json($service->provision(
            $data['external_subject'],
            $data['technical_handle'],
            $data['technical_email'],
            $data['display_seed'] ?? null,
        ));
    }

    public function read(Request $request, VinylHubAccountEdgeService $service): JsonResponse
    {
        $data = $request->validate([
            'external_subject' => ['required', 'string', 'max:255'],
            'repair' => ['nullable', 'boolean'],
        ]);

        return response()->json($service->read($data['external_subject'], $data['repair'] ?? true));
    }

    public function renew(Request $request, VinylHubAccountEdgeService $service): JsonResponse
    {
        return response()->json($service->renew($this->subject($request)));
    }

    public function revoke(Request $request, VinylHubAccountEdgeService $service): JsonResponse
    {
        return response()->json($service->revoke($this->subject($request)));
    }

    public function suspend(Request $request, VinylHubAccountEdgeService $service): JsonResponse
    {
        return response()->json($service->suspend($this->subject($request)));
    }

    public function resume(Request $request, VinylHubAccountEdgeService $service): JsonResponse
    {
        return response()->json($service->resume($this->subject($request)));
    }

    public function delete(Request $request, VinylHubAccountEdgeService $service): JsonResponse
    {
        return response()->json($service->delete($this->subject($request)));
    }

    public function deleteStatus(Request $request, VinylHubAccountEdgeService $service): JsonResponse
    {
        return response()->json($service->read($this->subject($request), false));
    }

    protected function subject(Request $request): string
    {
        return $request->validate([
            'external_subject' => ['required', 'string', 'max:255'],
        ])['external_subject'];
    }
}
