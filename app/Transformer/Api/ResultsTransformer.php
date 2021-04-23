<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Transformer\Api;

use League\Fractal;

class ResultsTransformer extends Fractal\TransformerAbstract
{

	protected $defaultIncludes = [
		'accounts',
		'statuses',
		'hashtags',
	];

	public function transform($results)
	{
		return [
			'accounts' => [],
			'statuses' => [],
			'hashtags' => []
		];
	}

	public function includeAccounts($results)
	{
		$accounts = $results->accounts;
		return $this->collection($accounts, new AccountTransformer());
	}

	public function includeStatuses($results)
	{
		$statuses = $results->statuses;
		return $this->collection($statuses, new StatusTransformer());
	}

	public function includeTags($results)
	{
		$hashtags = $status->hashtags;
		return $this->collection($hashtags, new HashtagTransformer());
	}
}
