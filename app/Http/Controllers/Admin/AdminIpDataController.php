<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpDataset;
use App\Models\User;
use App\Services\IpDataImportService;
use App\Services\IpDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AdminIpDataController extends Controller
{
    public const array RANGES = [
        '1d' => ['days' => 1, 'label' => 'Last 24 hours'],
        '7d' => ['days' => 7, 'label' => 'Last 7 days'],
        '30d' => ['days' => 30, 'label' => 'Last 30 days'],
    ];

    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request): View
    {
        $this->validate($request, [
            'ip' => 'nullable|string|max:64',
            'range' => ['nullable', Rule::in(array_keys(self::RANGES))],
        ]);

        $range = $request->input('range', '7d');
        $ip = $request->filled('ip') ? trim((string) $request->input('ip')) : null;
        $lookup = null;
        $sameIp = 0;
        $validIp = $ip !== null && IpDataService::key($ip) !== null;

        if ($validIp) {
            $lookup = IpDataService::lookup($ip);
            $sameIp = User::query()->where('app_register_ip', $ip)->count();
        }

        $datasets = IpDataService::datasets();
        $days = self::RANGES[$range]['days'];

        return view('admin.ip-data.index', [
            'datasets' => $datasets,
            'available' => IpDataService::available(),
            'running' => collect($datasets)->contains(fn ($d) => $d['running'] !== null),
            'history' => IpDataset::query()->with('creator:id,username')->orderByDesc('id')->limit(15)->get(),
            'ip' => $ip,
            'validIp' => $validIp,
            'lookup' => $lookup,
            'sameIp' => $sameIp,
            'range' => $range,
            'ranges' => self::RANGES,
            'topNetworks' => IpDataService::topSignups('signup_asn', $days),
            'topCountries' => IpDataService::topSignups('signup_country', $days, 6),
            'chunkSize' => IpDataImportService::chunkSize(),
            'zipSupported' => IpDataImportService::zipSupported(),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $this->validate($request, [
            'upload_id' => 'required|string|min:16|max:64',
            'offset' => 'required|integer|min:0',
            'total' => 'required|integer|min:1|max:'.IpDataImportService::MAX_UPLOAD,
            'name' => 'required|string|max:255',
            'final' => 'required|boolean',
            'chunk' => 'required|file|max:'.(int) ceil(IpDataImportService::CHUNK_MAX / 1024 + 64),
        ]);

        try {
            if ((int) $request->input('offset') === 0) {
                IpDataImportService::cleanupUploads();
            }

            $result = IpDataImportService::receiveChunk(
                (string) $request->input('upload_id'),
                (int) $request->input('offset'),
                (int) $request->input('total'),
                (string) $request->input('name'),
                $request->file('chunk'),
                $request->boolean('final'),
                $request->user()->id
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if ($result['expected_offset'] !== null) {
            return response()->json($result, 409);
        }

        return response()->json($result);
    }

    public function status(): JsonResponse
    {
        $datasets = IpDataset::query()
            ->whereIn('status', IpDataset::RUNNING)
            ->orWhere('updated_at', '>=', now()->subMinutes(10))
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return response()->json([
            'running' => $datasets->contains(fn (IpDataset $d) => $d->isRunning()),
            'datasets' => $datasets->map(fn (IpDataset $d) => $d->toApi())->values(),
        ]);
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        $dataset = IpDataset::findOrFail($id);

        $cancelled = IpDataImportService::cancel($dataset, 'Cancelled by @'.$request->user()->username.'.');

        return redirect()
            ->route('admin.ip-data')
            ->with('status', $cancelled ? 'Import cancelled. The active data was not changed.' : 'That import already finished.');
    }
}
