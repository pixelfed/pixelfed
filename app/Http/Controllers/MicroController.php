<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Transformer\Api\StatusTransformer;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\ArraySerializer;

#[Middleware('auth')]
class MicroController extends Controller
{
    public function composeText(Request $request): array
    {
        $this->validate($request, [
            'type' => [
                'required',
                'string',
                Rule::in(['text']),
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
                    'draft',
                ]),
            ],
        ]);
        $profile = $request->user()->profile;
        $title = $request->input('title');
        $content = $request->input('content');
        $visibility = $request->input('visibility');

        $status = DB::transaction(function () use ($profile, $content, $visibility, $title) {
            $status = new Status;
            $status->type = 'text';
            $status->profile_id = $profile->id;
            $status->caption = strip_tags($content);
            $status->is_nsfw = false;

            // TODO: remove deprecated visibility in favor of scope
            $status->visibility = $visibility;
            $status->scope = $visibility;
            $status->entities = json_encode(['title' => $title]);
            $status->save();

            return $status;
        });

        $fractal = new Manager;
        $fractal->setSerializer(new ArraySerializer);
        $s = new Item($status, new StatusTransformer);

        return $fractal->createData($s)->toArray();
    }
}
