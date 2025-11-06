<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MarkerService;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class PreferencesController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    /**
     * GET /api/v1/preferences
     *
     * @return array
     */
    public function getPreferences(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();

        $res = [
            'posting:default:visibility' => $user->profile->is_private ? 'private' : 'public',
            'posting:default:sensitive' => false,
            'posting:default:language' => $user->language ?? 'en',
            'reading:expand:media' => 'default',
            'reading:expand:spoilers' => false,
        ];

        return $this->json($res);
    }

    /**
     * GET /api/v1/trends
     *
     * @return array
     */
    public function getTrends(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return $this->json([]);
    }

    /**
     * GET /api/v1/announcements
     *
     * @return array
     */
    public function getAnnouncements(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return $this->json([]);
    }

    /**
     * GET /api/v1/markers
     *
     * @return array
     */
    public function getMarkers(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'timeline' => 'nullable|array',
            'timeline.*' => 'nullable|string|in:home,notifications',
        ]);

        $user = $request->user();
        $timelines = $request->input('timeline', ['home', 'notifications']);

        $res = [];
        foreach ($timelines as $timeline) {
            $marker = MarkerService::get($user->id, $timeline);
            if ($marker) {
                $res[$timeline] = $marker;
            }
        }

        return $this->json($res);
    }

    /**
     * POST /api/v1/markers
     *
     * @return array
     */
    public function setMarkers(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $this->validate($request, [
            'home[last_read_id]' => 'nullable|string',
            'notifications[last_read_id]' => 'nullable|string',
        ]);

        $user = $request->user();

        if ($request->has('home.last_read_id')) {
            MarkerService::set($user->id, 'home', $request->input('home.last_read_id'));
        }

        if ($request->has('notifications.last_read_id')) {
            MarkerService::set($user->id, 'notifications', $request->input('notifications.last_read_id'));
        }

        return $this->json([]);
    }
}