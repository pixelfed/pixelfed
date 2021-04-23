<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Transformer\Api;

use League\Fractal;

class FilterTransformer extends Fractal\TransformerAbstract
{
	public function transform()
	{
		return [
			'id' => (string) '',
			'phrase' => (string) '',
			'context' => [],
			'expires_at' => null,
			'irreversible' => (bool) false,
			'whole_word' => (bool) false
		];
	}
}
