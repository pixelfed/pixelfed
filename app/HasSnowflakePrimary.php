<?php

namespace App;

use App\Services\SnowflakeService;

trait HasSnowflakePrimary
{
	public static function bootHasSnowflakePrimary()
	{
		static::saving(function ($model) {
			if (is_null($model->getKey())) {
				$keyName = $model->getKeyName();
				$id = SnowflakeService::next();
				$model->setAttribute($keyName, $id);
			}
		});
	}
}
