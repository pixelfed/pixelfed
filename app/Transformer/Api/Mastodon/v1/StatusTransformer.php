<?php

namespace App\Transformer\Api\Mastodon\v1;

use App\Services\MediaService;
use App\Services\ProfileService;
use App\Services\StatusHashtagService;
use App\Status;
use App\Util\Lexer\Autolink;
use League\Fractal;

class StatusTransformer extends Fractal\TransformerAbstract
{

}
