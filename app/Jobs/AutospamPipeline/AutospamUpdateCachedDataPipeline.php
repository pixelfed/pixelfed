<?php

namespace App\Jobs\AutospamPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AutospamCustomTokens;
use Illuminate\Support\Facades\Storage;
use App\Services\AutospamService;
use Cache;

class AutospamUpdateCachedDataPipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/**
	 * Create a new job instance.
	 */
	public function __construct()
	{
	}

	/**
	 * Execute the job.
	 */
	public function handle(): void
	{
		$spamExists = Storage::exists(AutospamService::MODEL_SPAM_PATH);
		if($spamExists) {
			$spam = json_decode(Storage::get(AutospamService::MODEL_SPAM_PATH), true);
		} else {
			$spam = [
				'documents' => [
					'spam' => 0
				],
				'words' => [
					'spam' => []
				]
			];
		}
		$newSpam = AutospamCustomTokens::whereCategory('spam')->get();
		foreach($newSpam as $ns) {
			$key = strtolower($ns->token);
			if(isset($spam['words']['spam'][$key])) {
				$spam['words']['spam'][$key] = $spam['words']['spam'][$key] + $ns->weight;
			} else {
				$spam['words']['spam'][$key] = $ns->weight;
			}
		}
		$newSpamCount = count($spam['words']['spam']);
		if($newSpamCount) {
			$spam['documents']['spam'] = $newSpamCount;
			arsort($spam['words']['spam']);
			Storage::put(AutospamService::MODEL_SPAM_PATH, json_encode($spam, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
		}

		$hamExists = Storage::exists(AutospamService::MODEL_HAM_PATH);
		if($hamExists) {
			$ham = json_decode(Storage::get(AutospamService::MODEL_HAM_PATH), true);
		} else {
			$ham = [
				'documents' => [
					'ham' => 0
				],
				'words' => [
					'ham' => []
				]
			];
		}
		$newHam = AutospamCustomTokens::whereCategory('ham')->get();
		foreach($newHam as $ns) {
			$key = strtolower($ns->token);
			if(isset($spam['words']['ham'][$key])) {
				$ham['words']['ham'][$key] = $ham['words']['ham'][$key] + $ns->weight;
			} else {
				$ham['words']['ham'][$key] = $ns->weight;
			}
		}

		$newHamCount = count($ham['words']['ham']);
		if($newHamCount) {
			$ham['documents']['ham'] = $newHamCount;
			arsort($ham['words']['ham']);
			Storage::put(AutospamService::MODEL_HAM_PATH, json_encode($ham, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
		}

		if($newSpamCount && $newHamCount) {
			$combined = [
				'documents' => [
					'spam' => $newSpamCount,
					'ham' => $newHamCount,
				],
				'words' => [
					'spam' => $spam['words']['spam'],
					'ham' => $ham['words']['ham']
				]
			];

			Storage::put(AutospamService::MODEL_FILE_PATH, json_encode($combined, JSON_PRETTY_PRINT,JSON_UNESCAPED_SLASHES));
		}

		Cache::forget(AutospamService::MODEL_CACHE_KEY);
		Cache::forget(AutospamService::CHCKD_CACHE_KEY);
	}
}
