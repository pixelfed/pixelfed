<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationBlock;
use App\Services\RegistrationBlockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AdminRegistrationBlockController extends Controller
{
    public const array EXPIRY_OPTIONS = [
        'never' => 'Never',
        '1h' => '1 hour',
        '24h' => '24 hours',
        '7d' => '7 days',
        '30d' => '30 days',
        '90d' => '90 days',
    ];

    public function __construct()
    {
        $this->middleware(['auth', 'admin', 'dangerzone']);
    }

    public function index(Request $request): View
    {
        $this->validate($request, [
            'type' => ['nullable', Rule::in(array_merge(['all'], RegistrationBlockService::TYPES))],
            'status' => ['nullable', Rule::in(['all', 'active', 'expired', 'disabled'])],
            'q' => 'nullable|string|max:255',
        ]);

        $type = $request->input('type', 'all');
        $status = $request->input('status', 'active');
        $q = $request->input('q');

        $blocks = RegistrationBlock::query()
            ->when($type !== 'all', fn ($query) => $query->where('type', $type))
            ->when($status === 'active', fn ($query) => $query->enforced())
            ->when($status === 'disabled', fn ($query) => $query->where('is_active', false))
            ->when($status === 'expired', fn ($query) => $query->where('is_active', true)->whereNotNull('expires_at')->where('expires_at', '<=', now()))
            ->when($q, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $w->where('pattern', 'like', '%'.$q.'%')->orWhere('note', 'like', '%'.$q.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $counts = RegistrationBlock::query()
            ->enforced()
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        return view('admin.registration-blocks.index', [
            'blocks' => $blocks,
            'counts' => $counts,
            'type' => $type,
            'status' => $status,
            'q' => $q,
            'expiryOptions' => self::EXPIRY_OPTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'type' => ['required', Rule::in(RegistrationBlockService::TYPES)],
            'match_type' => ['required', Rule::in(RegistrationBlockService::MATCH_TYPES)],
            'patterns' => 'required|string|max:20000',
            'note' => 'nullable|string|max:490',
            'expires' => ['required', Rule::in(array_keys(self::EXPIRY_OPTIONS))],
        ]);

        $lines = collect(preg_split('/\r\n|\r|\n/', (string) $request->input('patterns')))
            ->map(fn ($line) => trim((string) $line))
            ->filter(fn ($line) => $line !== '' && ! str_starts_with($line, '#'))
            ->unique()
            ->take(500)
            ->values();

        if ($lines->isEmpty()) {
            return back()->withInput()->withErrors(['patterns' => 'Add at least one pattern.']);
        }

        $expiresAt = RegistrationBlockService::parseDuration($request->input('expires'));
        $added = 0;
        $errors = [];

        foreach ($lines as $line) {
            try {
                RegistrationBlockService::store(
                    $request->input('type'),
                    $request->input('match_type'),
                    $line,
                    $request->input('note'),
                    $expiresAt,
                    $request->user()->id
                );
                $added++;
            } catch (InvalidArgumentException $e) {
                $errors[] = $line.': '.$e->getMessage();
            }
        }

        $redirect = redirect()->route('admin.registration-blocks', ['type' => $request->input('type')]);

        if (count($errors)) {
            return $redirect
                ->withInput()
                ->withErrors(['patterns' => array_slice($errors, 0, 20)])
                ->with('status', $added ? "Added {$added} ".str('block')->plural($added).'.' : null);
        }

        return $redirect->with('status', "Added {$added} ".str('block')->plural($added).'.');
    }

    public function toggle(Request $request, int $id): RedirectResponse
    {
        $block = RegistrationBlock::findOrFail($id);
        $block->is_active = ! $block->is_active;
        $block->save();

        return back()->with('status', $block->is_active ? 'Block enabled.' : 'Block disabled.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        RegistrationBlock::findOrFail($id)->delete();

        return back()->with('status', 'Block deleted.');
    }

    public function test(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'test_ip' => 'nullable|string|max:64',
            'test_email' => 'nullable|string|max:255',
            'test_username' => 'nullable|string|max:64',
        ]);

        $ip = $request->filled('test_ip') ? $request->input('test_ip') : null;
        $email = $request->filled('test_email') ? $request->input('test_email') : null;
        $username = $request->filled('test_username') ? $request->input('test_username') : null;

        if ($ip === null && $email === null && $username === null) {
            return back()->withInput()->withErrors(['test' => 'Enter an IP, email or username to test.']);
        }

        $match = RegistrationBlockService::check($ip, $email, $username, false);

        return back()->withInput()->with('test_result', [
            'blocked' => $match !== null,
            'rule' => $match,
        ]);
    }
}
