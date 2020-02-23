<?php

namespace App\Transformer\Api;

use League\Fractal;

class EmojiTransformer extends Fractal\TransformerAbstract
{
	public function transform($emoji): array
	{
		return [
			'shortcode' 			=> '',
			'static_url'			=> '',
			'url'					=> '',
			'visible_in_picker' 	=> false
		];
	}
}
