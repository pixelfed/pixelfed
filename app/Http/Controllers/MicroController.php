<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Profile;
use App\Status;
use Auth;
use DB;
use Purify;
use Illuminate\Validation\Rule;

class MicroController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function composeText(Request $request)
    {
        $this->validate($request, [
            'type' => [
                'required',
                'string',
                Rule::in(['text'])
            ],
            'title' => 'nullable|string|max:140',
            'content' => 'required|string|max:500',
            'visibility' => [
                'required',
                'string',
                Rule::in([
                    'public',
                    'unlisted',
                    'private',
                    'draft'
                ])
            ]
        ]);
        $profile = Auth::user()->profile;
        $title = $request->input('title');
        $content = $request->input('content');
        $visibility = $request->input('visibility');

        $status = DB::transaction(function () use ($profile, $content, $visibility, $title) {
            $status = new Status;
            $status->type = 'text';
            $status->profile_id = $profile->id;
            $status->caption = strip_tags($content);
            $status->rendered = Purify::clean($content);
            $status->is_nsfw = false;

            // TODO: remove deprecated visibility in favor of scope
            $status->visibility = $visibility;
            $status->scope = $visibility;
            $status->entities = json_encode(['title'=>$title]);
            $status->save();
            return $status;
        });

        $fractal = new \League\Fractal\Manager();
        $fractal->setSerializer(new \League\Fractal\Serializer\ArraySerializer());
        $s = new \League\Fractal\Resource\Item($status, new \App\Transformer\Api\StatusTransformer());
        return $fractal->createData($s)->toArray();
    }
}
