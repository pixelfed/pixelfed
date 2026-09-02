<?php

namespace App\Http\Controllers\Api\V1Dot1;

use App\Jobs\ReportPipeline\ReportNotifyAdminViaEmail;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\Report;
use App\Models\Status;
use App\Models\Story;
use App\Services\BouncerService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

trait Reports
{
    const REPORT_TYPES = [
        'spam',
        'sensitive',
        'abusive',
        'underage',
        'violence',
        'copyright',
        'impersonation',
        'scam',
        'terrorism',
    ];

    public function report(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        abort_if($user->status != null, 403);

        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $validator = Validator::make($request->all(), [
            'report_type' => ['required', 'string', Rule::in(self::REPORT_TYPES)],
            'object_id' => ['required'],
            'object_type' => ['required', 'string', Rule::in(['post', 'user', 'story'])],
            'message' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error('Invalid or missing parameters', 400, ['error_code' => 'ERROR_INVALID_PARAMS']);
        }

        $reportType = $request->input('report_type');
        $objectId = $request->input('object_id');
        $objectType = $request->input('object_type');

        $message = $this->sanitizeReportMessage($request->input('message'));

        if ($message === false) {
            return $this->error('Message is too long', 400, ['error_code' => 'ERROR_MESSAGE_TOO_LONG']);
        }

        [$object, $modelClass, $reportedProfileId] = match ($objectType) {
            'post' => [$post = Status::find($objectId), Status::class, $post?->profile_id],
            'user' => [$profile = Profile::find($objectId), Profile::class, $profile?->id],
            'story' => [$story = Story::whereActive(true)->find($objectId), Story::class, $story?->profile_id],
        };

        if (! $object) {
            return $this->error('Invalid object id', 400, ['error_code' => 'ERROR_INVALID_OBJECT_ID']);
        }

        if ($objectType === 'story') {
            $follows = Follower::whereProfileId($user->profile_id)
                ->whereFollowingId($object->profile_id)
                ->exists();

            if (! $follows) {
                return $this->error('Invalid object id', 400, ['error_code' => 'ERROR_INVALID_OBJECT_ID']);
            }
        }

        if ($reportedProfileId == $user->profile_id) {
            return $this->error('Cannot self report', 400, ['error_code' => 'ERROR_NO_SELF_REPORTS']);
        }

        $exists = Report::whereUserId($user->id)
            ->whereObjectId($object->id)
            ->whereObjectType($modelClass)
            ->exists();

        if ($exists) {
            return $this->error('Duplicate report', 400, ['error_code' => 'ERROR_REPORT_DUPLICATE']);
        }

        $report = new Report;
        $report->profile_id = $user->profile_id;
        $report->user_id = $user->id;
        $report->object_id = $object->id;
        $report->object_type = $modelClass;
        $report->reported_profile_id = $reportedProfileId;
        $report->type = $reportType;
        $report->message = $message;
        $report->save();

        if (config('instance.reports.email.enabled')) {
            ReportNotifyAdminViaEmail::dispatch($report)->onQueue('default');
        }

        return $this->json([
            'msg' => 'Successfully sent report',
            'code' => 200,
        ]);
    }

    protected function sanitizeReportMessage(?string $message): string|false|null
    {
        if (! $message) {
            return null;
        }

        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{200B}-\x{200F}\x{FEFF}]/u', '', $message);
        $clean = trim(preg_replace('/\s+/u', ' ', $clean));

        if ($clean === '') {
            return null;
        }

        if (strlen($clean) > 255) {
            return false;
        }

        return $clean;
    }

    public function moderatePost(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_if($request->user()->is_admin != true, 403);
        abort_unless($request->user()->tokenCan('admin:write'), 403);

        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $this->validate($request, [
            'action' => 'required|in:cw,mark-public,mark-unlisted,mark-private,mark-spammer,delete',
        ]);

        $action = $request->input('action');
        $status = Status::find($id);

        if (! $status) {
            return response()->json(['error' => 'Cannot find status'], 400);
        }

        if ($status->uri == null) {
            if ($status->profile->user && $status->profile->user->is_admin) {
                return response()->json(['error' => 'Cannot moderate admin accounts'], 400);
            }
        }

        if ($action == 'mark-spammer') {
            $status->profile->update([
                'unlisted' => true,
                'cw' => true,
                'no_autolink' => true,
            ]);

            Status::whereProfileId($status->profile_id)
                ->get()
                ->each(function ($s) {
                    if (in_array($s->scope, ['public', 'unlisted'])) {
                        $s->scope = 'private';
                        $s->visibility = 'private';
                    }
                    $s->is_nsfw = true;
                    $s->save();
                    StatusService::del($s->id, true);
                });

            Cache::forget('pf:bouncer_v0:exemption_by_pid:'.$status->profile_id);
            Cache::forget('pf:bouncer_v0:recent_by_pid:'.$status->profile_id);
            Cache::forget('admin-dash:reports:spam-count');
        } elseif ($action == 'cw') {
            $state = $status->is_nsfw;
            $status->is_nsfw = ! $state;
            $status->save();
            StatusService::del($status->id);
        } elseif ($action == 'mark-public') {
            $state = $status->scope;
            $status->scope = 'public';
            $status->visibility = 'public';
            $status->save();
            StatusService::del($status->id, true);
            if ($state !== 'public') {
                if ($status->uri) {
                    if ($status->in_reply_to_id == null && $status->reblog_of_id == null) {
                        NetworkTimelineService::add($status->id);
                    }
                } else {
                    if ($status->in_reply_to_id == null && $status->reblog_of_id == null) {
                        PublicTimelineService::add($status->id);
                    }
                }
            }
        } elseif ($action == 'mark-unlisted') {
            $state = $status->scope;
            $status->scope = 'unlisted';
            $status->visibility = 'unlisted';
            $status->save();
            StatusService::del($status->id);
            if ($state == 'public') {
                PublicTimelineService::del($status->id);
                NetworkTimelineService::del($status->id);
            }
        } elseif ($action == 'mark-private') {
            $state = $status->scope;
            $status->scope = 'private';
            $status->visibility = 'private';
            $status->save();
            StatusService::del($status->id);
            if ($state == 'public') {
                PublicTimelineService::del($status->id);
                NetworkTimelineService::del($status->id);
            }
        } elseif ($action == 'delete') {
            PublicTimelineService::del($status->id);
            NetworkTimelineService::del($status->id);
            Cache::forget('_api:statuses:recent_9:'.$status->profile_id);
            Cache::forget('profile:status_count:'.$status->profile_id);
            Cache::forget('profile:embed:'.$status->profile_id);
            StatusService::del($status->id, true);
            Cache::forget('profile:status_count:'.$status->profile_id);
            $status->uri ? RemoteStatusDelete::dispatch($status) : StatusDelete::dispatch($status);

            return [];
        }

        Cache::forget('_api:statuses:recent_9:'.$status->profile_id);

        return StatusService::get($status->id, false);
    }
}
