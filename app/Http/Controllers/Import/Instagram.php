<?php

namespace App\Http\Controllers\Import;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Auth, DB;
use App\{
	ImportData,
	ImportJob,
	Profile, 
	User
};

trait Instagram
{
    public function instagram()
    {
      return view('settings.import.instagram.home');
    }

    public function instagramStart(Request $request)
    {	
    	$job = $this->instagramRedirectOrNew();
    	return redirect($job->url());
    }

    protected function instagramRedirectOrNew()
    {
    	$profile = Auth::user()->profile;
    	$exists = ImportJob::whereProfileId($profile->id)
    		->whereService('instagram')
    		->whereNull('completed_at')
    		->exists();
    	if($exists) {
    		$job = ImportJob::whereProfileId($profile->id)
    		->whereService('instagram')
    		->whereNull('completed_at')
    		->first();
    	} else {
    		$job = new ImportJob;
    		$job->profile_id = $profile->id;
    		$job->service = 'instagram';
    		$job->uuid = (string) Str::uuid();
    		$job->stage = 1;
    		$job->save();
    	}
    	return $job;
    }

    public function instagramStepOne(Request $request, $uuid)
    {
    	$profile = Auth::user()->profile;
    	$job = ImportJob::whereProfileId($profile->id)
    		->whereNull('completed_at')
    		->whereUuid($uuid)
    		->whereStage(1)
    		->firstOrFail();
    	return view('settings.import.instagram.step-one', compact('profile', 'job'));
    }

    public function instagramStepOneStore(Request $request, $uuid)
    {
    	$this->validate($request, [
    		'media.*' => 'required|mimes:bin,jpeg,png,gif|max:500',
    		//'mediajson' => 'required|file|mimes:json'
    	]);
    	$media = $request->file('media');

    	$profile = Auth::user()->profile;
    	$job = ImportJob::whereProfileId($profile->id)
    		->whereNull('completed_at')
    		->whereUuid($uuid)
    		->whereStage(1)
    		->firstOrFail();
    		
        foreach ($media as $k => $v) {
        	$original = $v->getClientOriginalName();
    		if(strlen($original) < 32 || $k > 100) {
    			continue;
    		}
            $storagePath = "import/{$job->uuid}";
            $path = $v->store($storagePath);
            DB::transaction(function() use ($profile, $job, $path, $original) {
		        $data = new ImportData;
		        $data->profile_id = $profile->id;
		        $data->job_id = $job->id;
		        $data->service = 'instagram';
		        $data->path = $path;
		        $data->stage = $job->stage;
		        $data->original_name = $original;
		        $data->save();
            });
        }
        DB::transaction(function() use ($profile, $job) {
        	$job->stage = 2;
        	$job->save();
    	});
        return redirect($job->url());
    	return view('settings.import.instagram.step-one', compact('profile', 'job'));
    }

    public function instagramStepTwo(Request $request, $uuid)
    {
    	$profile = Auth::user()->profile;
    	$job = ImportJob::whereProfileId($profile->id)
    		->whereNull('completed_at')
    		->whereUuid($uuid)
    		->whereStage(2)
    		->firstOrFail();
    	return view('settings.import.instagram.step-two', compact('profile', 'job'));
    }

    public function instagramStepTwoStore(Request $request, $uuid)
    {
    	$this->validate($request, [
    		'media' => 'required|file|max:1000'
    	]);
    	$profile = Auth::user()->profile;
    	$job = ImportJob::whereProfileId($profile->id)
    		->whereNull('completed_at')
    		->whereUuid($uuid)
    		->whereStage(2)
    		->firstOrFail();
    	$media = $request->file('media');
    	$file = file_get_contents($media);
		$json = json_decode($file, true, 5);
		if(!$json || !isset($json['photos'])) {
			return abort(500);
		}
		$storagePath = "import/{$job->uuid}";
        $path = $media->store($storagePath);
        $job->media_json = $path;
        $job->stage = 3;
        $job->save();
        return redirect($job->url());
		return $json;

    }

    public function instagramStepThree(Request $request, $uuid)
    {
    	$profile = Auth::user()->profile;
    	$job = ImportJob::whereProfileId($profile->id)
    		->whereNull('completed_at')
    		->whereUuid($uuid)
    		->whereStage(3)
    		->firstOrFail();
    	return view('settings.import.instagram.step-three', compact('profile', 'job'));
    }
}
