<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Models\CustomEmoji;
use App\Services\MediaService;
use App\Status;
use App\Util\Lexer\Autolink;
use Illuminate\Support\Str;
use League\Fractal;

class CreateNote extends Fractal\TransformerAbstract
{

}
