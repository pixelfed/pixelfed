<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\RegistrationBlock;
use App\Services\RegistrationBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class RegistrationBlocksController extends ApiController
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'api.admin', 'scope:admin:read,admin:read:registration_blocks'])->only(['index', 'show', 'test']);
        $this->middleware(['auth:api', 'api.admin', 'scope:admin:write,admin:write:registration_blocks'])->only(['create', 'update', 'delete']);
    }

    public function index(Request $request): JsonResponse
    {
        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1|max:100',
            'type' => ['sometimes', Rule::in(RegistrationBlockService::TYPES)],
            'status' => ['sometimes', Rule::in(['all', 'active', 'expired', 'disabled'])],
            'q' => 'sometimes|nullable|string|max:255',
        ]);

        $status = $request->input('status', 'all');

        $res = RegistrationBlock::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($status === 'active', fn ($q) => $q->enforced())
            ->when($status === 'disabled', fn ($q) => $q->where('is_active', false))
            ->when($status === 'expired', fn ($q) => $q->where('is_active', true)->whereNotNull('expires_at')->where('expires_at', '<=', now()))
            ->when($request->filled('q'), fn ($q) => $q->where('pattern', 'like', '%'.$request->input('q').'%'))
            ->orderByDesc('id')
            ->cursorPaginate((int) $request->input('limit', 40))
            ->withQueryString();

        return $this->json(
            collect($res->items())->map(fn (RegistrationBlock $b) => $b->toApi())->values(),
            ['Link' => $this->linksForCollection($res)]
        );
    }

    public function show(Request $request, $id): JsonResponse
    {
        $block = RegistrationBlock::find($id);

        if (! $block) {
            return $this->json(['error' => 'Record not found'], [], 404);
        }

        return $this->json($block->toApi());
    }

    public function create(Request $request): JsonResponse
    {
        $this->validate($request, [
            'type' => ['required', Rule::in(RegistrationBlockService::TYPES)],
            'match_type' => ['sometimes', Rule::in(RegistrationBlockService::MATCH_TYPES)],
            'pattern' => 'required|string|max:255',
            'note' => 'sometimes|nullable|string|max:490',
            'expires_in' => 'sometimes|nullable|integer|min:60|max:31536000',
        ]);

        $expiresAt = $request->filled('expires_in') ? now()->addSeconds((int) $request->input('expires_in')) : null;

        try {
            $block = RegistrationBlockService::store(
                $request->input('type'),
                $request->input('match_type', 'exact'),
                $request->input('pattern'),
                $request->input('note'),
                $expiresAt,
                $request->user()->id
            );
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], [], 422);
        }

        return $this->json($block->toApi());
    }

    public function update(Request $request, $id): JsonResponse
    {
        $this->validate($request, [
            'is_active' => 'sometimes|boolean',
            'note' => 'sometimes|nullable|string|max:490',
            'expires_in' => 'sometimes|nullable|integer|min:0|max:31536000',
        ]);

        $block = RegistrationBlock::find($id);

        if (! $block) {
            return $this->json(['error' => 'Record not found'], [], 404);
        }

        if ($request->has('is_active')) {
            $block->is_active = $request->boolean('is_active');
        }

        if ($request->has('note')) {
            $note = trim((string) $request->input('note'));
            $block->note = $note !== '' ? $note : null;
        }

        if ($request->has('expires_in')) {
            $seconds = (int) $request->input('expires_in');
            $block->expires_at = $seconds > 0 ? Carbon::now()->addSeconds($seconds) : null;
        }

        $block->save();

        return $this->json($block->toApi());
    }

    public function delete(Request $request, $id): JsonResponse
    {
        $block = RegistrationBlock::find($id);

        if (! $block) {
            return $this->json(['error' => 'Record not found'], [], 404);
        }

        $block->delete();

        return $this->json([]);
    }

    public function test(Request $request): JsonResponse
    {
        $this->validate($request, [
            'ip' => 'sometimes|nullable|string|max:64',
            'email' => 'sometimes|nullable|string|max:255',
            'username' => 'sometimes|nullable|string|max:64',
        ]);

        $match = RegistrationBlockService::check(
            $request->filled('ip') ? $request->input('ip') : null,
            $request->filled('email') ? $request->input('email') : null,
            $request->filled('username') ? $request->input('username') : null,
            false
        );

        return $this->json([
            'blocked' => $match !== null,
            'rule' => $match ? RegistrationBlock::find($match['id'])?->toApi() : null,
        ]);
    }
}
