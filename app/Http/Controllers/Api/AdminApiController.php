<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\StatusPipeline\StatusDelete;
use Auth;
use Cache;
use Carbon\Carbon;
use App\Like;
use App\Media;
use App\Profile;
use App\Status;

use App\Services\NotificationService;

class AdminApiController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function activity(Request $request)
    {
        $activity = [];
        
        $limit = request()->input('limit', 20);

        $activity['captions'] = Status::select(
            'id', 
            'caption', 
            'rendered', 
            'uri', 
            'profile_id',
            'type',
            'in_reply_to_id',
            'reblog_of_id',
            'is_nsfw',
            'scope',
            'created_at'
        )->whereNull('in_reply_to_id')
        ->whereNull('reblog_of_id')
        ->orderByDesc('created_at')
        ->paginate($limit);

        $activity['comments'] = Status::select(
            'id', 
            'caption', 
            'rendered', 
            'uri', 
            'profile_id',
            'type',
            'in_reply_to_id',
            'reblog_of_id',
            'is_nsfw',
            'scope',
            'created_at'
        )->whereNotNull('in_reply_to_id')
        ->whereNull('reblog_of_id')
        ->orderByDesc('created_at')
        ->paginate($limit);

        return response()->json($activity, 200, [], JSON_PRETTY_PRINT);
    }

    public function moderateStatus(Request $request)
    {
        abort(400, 'Unpublished API');
        return;
        $this->validate($request, [
            'type' => 'required|string|in:status,profile',
            'id'   => 'required|integer|min:1',
            'action' => 'required|string|in:cw,unlink,unlist,suspend,delete'
        ]);

        $type = $request->input('type');
        $id = $request->input('id');
        $action = $request->input('action');

        if ($type == 'status') {
            $status = Status::findOrFail($id);
            switch ($action) {
                case 'cw':
                    $status->is_nsfw = true;
                    $status->save();
                    break;
                case 'unlink':
                    $status->rendered = $status->caption;
                    $status->save();
                    break;
                case 'unlist':
                    $status->scope = 'unlisted';
                    $status->visibility = 'unlisted';
                    $status->save();
                    break;
                
                default:
                    break;
            }
        } else if ($type == 'profile') {
            $profile = Profile::findOrFail($id);
            switch ($action) {

                case 'delete':
                    StatusDelete::dispatch($status);
                    break;
                
                default:
                    break;
            }
        }

    }

}