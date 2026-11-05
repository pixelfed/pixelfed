<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationBlock;
use App\Services\RegistrationBlockExchangeService;
use App\Services\RegistrationBlockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

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
            'pattern' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:490',
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
            'prefill' => [
                'pattern' => $request->input('pattern'),
                'note' => $request->input('note'),
            ],
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

    public function export(Request $request): Response
    {
        $this->validate($request, [
            'format' => ['nullable', Rule::in(RegistrationBlockExchangeService::FORMATS)],
            'types' => 'nullable|array',
            'types.*' => [Rule::in(RegistrationBlockService::TYPES)],
            'notes' => 'nullable|boolean',
            'inactive' => 'nullable|boolean',
        ]);

        $format = $request->input('format', 'json');
        $payload = RegistrationBlockExchangeService::export(
            (array) $request->input('types', RegistrationBlockService::TYPES),
            $request->boolean('notes'),
            $request->boolean('inactive')
        );

        $body = $format === 'csv'
            ? RegistrationBlockExchangeService::toCsv($payload)
            : RegistrationBlockExchangeService::toJson($payload);

        return response($body, 200, [
            'Content-Type' => $format === 'csv' ? 'text/csv; charset=UTF-8' : 'application/json',
            'Content-Disposition' => 'attachment; filename="'.RegistrationBlockExchangeService::filename($format).'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'file' => 'nullable|file|max:2048',
            'contents' => 'nullable|string|max:2000000',
            'types' => 'required|array|min:1',
            'types.*' => [Rule::in(RegistrationBlockService::TYPES)],
            'expires' => ['required', Rule::in(array_merge(['file'], array_keys(self::EXPIRY_OPTIONS)))],
            'import_note' => 'nullable|string|max:490',
            'dry_run' => 'nullable|boolean',
        ]);

        $contents = $request->hasFile('file')
            ? (string) file_get_contents($request->file('file')->getRealPath())
            : (string) $request->input('contents');

        $format = null;
        if ($request->hasFile('file')) {
            $ext = strtolower((string) $request->file('file')->getClientOriginalExtension());
            $format = in_array($ext, RegistrationBlockExchangeService::FORMATS, true) ? $ext : null;
        }

        try {
            $rows = RegistrationBlockExchangeService::parse($contents, $format);
        } catch (InvalidArgumentException $e) {
            return back()->withInput($request->except('file'))->withErrors(['import' => $e->getMessage()]);
        }

        $result = RegistrationBlockExchangeService::import($rows, [
            'types' => (array) $request->input('types'),
            'expires' => $request->input('expires') === 'file'
                ? false
                : RegistrationBlockService::parseDuration($request->input('expires')),
            'note' => $request->input('import_note'),
            'dry_run' => $request->boolean('dry_run'),
            'created_by' => $request->user()->id,
        ]);

        $result['invalid'] = array_slice($result['invalid'], 0, 25);
        $result['broad'] = array_slice($result['broad'], 0, 25);

        $redirect = redirect()->route('admin.registration-blocks')->with('import_result', $result);

        if ($result['dry_run']) {
            return $redirect->withInput($request->except('file'));
        }

        return $redirect->with('status', 'Imported '.$result['added'].' '.Str::plural('block', $result['added']).'.');
    }
}
