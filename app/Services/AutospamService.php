<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Util\Lexer\Classifier;

class AutospamService
{
	const CHCKD_CACHE_KEY = 'pf:services:autospam:nlp:checked';
	const MODEL_CACHE_KEY = 'pf:services:autospam:nlp:model-cache';
	const MODEL_FILE_PATH = 'nlp/active-training-data.json';
	const MODEL_SPAM_PATH = 'nlp/spam.json';
	const MODEL_HAM_PATH  = 'nlp/ham.json';

	public static function check($text)
	{
		if(!$text || strlen($text) == 0) {
			false;
		}
		if(!self::active()) {
			return null;
		}
		$model = self::getCachedModel();
		$classifier = new Classifier;
		$classifier->import($model['documents'], $model['words']);
		return $classifier->most($text) === 'spam';
	}

	public static function eligible()
	{
		return Cache::remember(self::CHCKD_CACHE_KEY, 86400, function() {
			if(!config_cache('pixelfed.bouncer.enabled') || !config('autospam.enabled')) {
				return false;
			}

			if(!Storage::exists(self::MODEL_SPAM_PATH)) {
				return false;
			}

			if(!Storage::exists(self::MODEL_HAM_PATH)) {
				return false;
			}

			if(!Storage::exists(self::MODEL_FILE_PATH)) {
				return false;
			} else {
				if(Storage::size(self::MODEL_FILE_PATH) < 1000) {
					return false;
				}
			}

			return true;
		});
	}

	public static function active()
	{
		return config_cache('autospam.nlp.enabled') && self::eligible();
	}

	public static function getCachedModel()
	{
		if(!self::active()) {
			return null;
		}

		return Cache::remember(self::MODEL_CACHE_KEY, 86400, function() {
			$res = Storage::get(self::MODEL_FILE_PATH);
			if(!$res || empty($res)) {
				return null;
			}

			return json_decode($res, true);
		});
	}
}
