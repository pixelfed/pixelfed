<?php

namespace App\Http\Controllers;

use App\Follower;
use App\Http\Resources\ImportStatus;
use App\Models\ImportPost;
use App\Services\ImportService;
use App\User;
use Illuminate\Http\Request;

class ImportPostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getConfig(Request $request)
    {
        return [
            'enabled' => config('import.instagram.enabled'),

            'limits' => [
                'max_posts' => config('import.instagram.limits.max_posts'),
                'max_attempts' => config('import.instagram.limits.max_attempts'),
            ],

            'allow_video_posts' => config('import.instagram.allow_video_posts'),

            'allow_image_webp' => config('import.instagram.allow_image_webp') && str_contains(config_cache('pixelfed.media_types'), 'image/webp'),

            'permissions' => [
                'admins_only' => config('import.instagram.permissions.admins_only'),
                'admin_follows_only' => config('import.instagram.permissions.admin_follows_only'),
                'min_account_age' => config('import.instagram.permissions.min_account_age'),
                'min_follower_count' => config('import.instagram.permissions.min_follower_count'),
            ],

            'allowed' => $this->checkPermissions($request, false),
        ];
    }

    public function getProcessingCount(Request $request)
    {
        abort_unless(config('import.instagram.enabled'), 404);

        $processing = ImportPost::whereProfileId($request->user()->profile_id)
            ->whereNull('status_id')
            ->whereSkipMissingMedia(false)
            ->count();

        $finished = ImportPost::whereProfileId($request->user()->profile_id)
            ->whereNotNull('status_id')
            ->whereSkipMissingMedia(false)
            ->count();

        return response()->json([
            'processing_count' => $processing,
            'finished_count' => $finished,
        ]);
    }

    public function getImportedFiles(Request $request)
    {
        abort_unless(config('import.instagram.enabled'), 404);

        return response()->json(
            ImportService::getImportedFiles($request->user()->profile_id),
            200,
            [],
            JSON_UNESCAPED_SLASHES
        );
    }

    public function getImportedPosts(Request $request)
    {
        abort_unless(config('import.instagram.enabled'), 404);

        return ImportStatus::collection(
            ImportPost::whereProfileId($request->user()->profile_id)
                ->has('status')
                ->cursorPaginate(9)
        );
    }

    public function formatHashtags($val = false)
    {
        if (! $val || ! strlen($val)) {
            return null;
        }

        $groupedHashtagRegex = '/#\w+(?=#)/';

        return preg_replace($groupedHashtagRegex, '$0 ', $val);
    }

    public function store(Request $request)
    {
        abort_unless(config('import.instagram.enabled'), 404);
        $this->checkPermissions($request);

        $uid = $request->user()->id;
        $pid = $request->user()->profile_id;
        $successCount = 0;
        $errors = [];

        foreach ($request->input('files') as $file) {
            try {
                $media = $file['media'];
                $c = collect($media);

                $firstUri = isset($media[0]['uri']) ? $media[0]['uri'] : '';
                $postHash = hash('sha256', $c->toJson().$firstUri);

                $exists = ImportPost::where('user_id', $uid)
                    ->where('post_hash', $postHash)
                    ->exists();

                if ($exists) {
                    $errors[] = 'Duplicate post detected. Skipping...';

                    continue;
                }

                $exts = $c->map(function ($m) {
                    $fn = last(explode('/', $m['uri']));

                    return last(explode('.', $fn));
                });

                $postType = $this->determinePostType($exts);

                $ip = new ImportPost;
                $ip->user_id = $uid;
                $ip->profile_id = $pid;
                $ip->post_hash = $postHash;
                $ip->service = 'instagram';
                $ip->post_type = $postType;
                $ip->media_count = $c->count();

                $ip->media = $c->map(function ($m) {
                    return [
                        'uri' => $m['uri'],
                        'title' => $this->formatHashtags($m['title'] ?? ''),
                        'creation_timestamp' => $m['creation_timestamp'] ?? null,
                    ];
                })->toArray();

                $ip->caption = $c->count() > 1 ?
                    $this->formatHashtags($file['title'] ?? '') :
                    $this->formatHashtags($ip->media[0]['title'] ?? '');

                $originalFilename = last(explode('/', $ip->media[0]['uri'] ?? ''));
                $ip->filename = $this->sanitizeFilename($originalFilename);

                $ip->metadata = $c->map(function ($m) {
                    return [
                        'uri' => $m['uri'],
                        'media_metadata' => isset($m['media_metadata']) ? $m['media_metadata'] : null,
                    ];
                })->toArray();

                $creationTimestamp = $c->count() > 1 ?
                    ($file['creation_timestamp'] ?? null) :
                    ($media[0]['creation_timestamp'] ?? null);

                if ($creationTimestamp) {
                    $ip->creation_date = now()->parse($creationTimestamp);
                    $ip->creation_year = $ip->creation_date->format('y');
                    $ip->creation_month = $ip->creation_date->format('m');
                    $ip->creation_day = $ip->creation_date->format('d');
                } else {
                    $ip->creation_date = now();
                    $ip->creation_year = now()->format('y');
                    $ip->creation_month = now()->format('m');
                    $ip->creation_day = now()->format('d');
                }

                $ip->save();
                $successCount++;

                ImportService::getImportedFiles($pid, true);
                ImportService::getPostCount($pid, true);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
                \Log::error('Import error: '.$e->getMessage());

                continue;
            }
        }

        return [
            'success' => true,
            'msg' => 'Successfully imported '.$successCount.' posts',
            'errors' => $errors,
        ];
    }

    public function storeMedia(Request $request)
    {
        abort_unless(config('import.instagram.enabled'), 404);

        $this->checkPermissions($request);

        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/jpg'];

        if (config('import.instagram.allow_image_webp') && str_contains(config_cache('pixelfed.media_types'), 'image/webp')) {
            $allowedMimeTypes[] = 'image/webp';
        }

        if (config('import.instagram.allow_video_posts')) {
            $allowedMimeTypes[] = 'video/mp4';
        }

        $mimes = 'mimetypes:'.implode(',', $allowedMimeTypes);

        $this->validate($request, [
            'file' => 'required|array|max:10',
            'file.*' => [
                'required',
                'file',
                $mimes,
                'max:'.config_cache('pixelfed.max_photo_size'),
            ],
        ]);

        $localFs = config('filesystems.default') === 'local';
        $disk = $localFs ? 'local' : config('filesystems.default');

        foreach ($request->file('file') as $file) {
            $extension = $file->getClientOriginalExtension();

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
            $fileName = $safeFilename.'.'.$extension;

            $file->storeAs('imports/'.$request->user()->id.'/', $fileName, $disk);
        }

        ImportService::getImportedFiles($request->user()->profile_id, true);

        return [
            'msg' => 'Success',
        ];
    }

    private function determinePostType($exts)
    {
        if ($exts->count() > 1) {
            if ($exts->contains('mp4')) {
                if ($exts->contains('jpg', 'png', 'webp')) {
                    return 'photo:video:album';
                } else {
                    return 'video:album';
                }
            } else {
                return 'photo:album';
            }
        } else {
            if ($exts->isEmpty()) {
                return 'photo';
            }

            $ext = $exts[0];

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                return 'photo';
            } elseif (in_array($ext, ['mp4'])) {
                return 'video';
            } else {
                return 'photo';
            }
        }
    }

    private function sanitizeFilename($filename)
    {
        $parts = explode('.', $filename);
        $extension = array_pop($parts);
        $originalName = implode('.', $parts);

        $safeFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);

        return $safeFilename.'.'.$extension;
    }

    protected function checkPermissions($request, $abortOnFail = true)
    {
        $user = $request->user();

        if ($abortOnFail) {
            abort_unless(config('import.instagram.enabled'), 404);
        }

        if ($user->is_admin) {
            if (! $abortOnFail) {
                return true;
            } else {
                return;
            }
        }

        $admin = User::whereIsAdmin(true)->first();

        if (config('import.instagram.permissions.admins_only')) {
            if ($abortOnFail) {
                abort_unless($user->is_admin, 404, 'Only admins can use this feature.');
            } else {
                if (! $user->is_admin) {
                    return false;
                }
            }
        }

        if (config('import.instagram.permissions.admin_follows_only')) {
            $exists = Follower::whereProfileId($admin->profile_id)
                ->whereFollowingId($user->profile_id)
                ->exists();
            if ($abortOnFail) {
                abort_unless(
                    $exists,
                    404,
                    'Only admins, and accounts they follow can use this feature'
                );
            } else {
                if (! $exists) {
                    return false;
                }
            }
        }

        if (config('import.instagram.permissions.min_account_age')) {
            $res = $user->created_at->lt(
                now()->subDays(config('import.instagram.permissions.min_account_age'))
            );
            if ($abortOnFail) {
                abort_unless(
                    $res,
                    404,
                    'Your account is too new to use this feature'
                );
            } else {
                if (! $res) {
                    return false;
                }
            }
        }

        if (config('import.instagram.permissions.min_follower_count')) {
            $res = Follower::whereFollowingId($user->profile_id)->count() >= config('import.instagram.permissions.min_follower_count');
            if ($abortOnFail) {
                abort_unless(
                    $res,
                    404,
                    'You don\'t have enough followers to use this feature'
                );
            } else {
                if (! $res) {
                    return false;
                }
            }
        }

        if (intval(config('import.instagram.limits.max_posts')) > 0) {
            $res = ImportService::getPostCount($user->profile_id) >= intval(config('import.instagram.limits.max_posts'));
            if ($abortOnFail) {
                abort_if(
                    $res,
                    404,
                    'You have reached the limit of post imports and cannot import any more posts'
                );
            } else {
                if ($res) {
                    return false;
                }
            }
        }

        if (intval(config('import.instagram.limits.max_attempts')) > 0) {
            $res = ImportService::getAttempts($user->profile_id) >= intval(config('import.instagram.limits.max_attempts'));
            if ($abortOnFail) {
                abort_if(
                    $res,
                    404,
                    'You have reached the limit of post import attempts and cannot import any more posts'
                );
            } else {
                if ($res) {
                    return false;
                }
            }
        }

        if (! $abortOnFail) {
            return true;
        }
    }
}
