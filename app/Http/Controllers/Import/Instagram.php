<?php

namespace App\Http\Controllers\Import;

use App\ImportData;
use App\ImportJob;
use App\Jobs\ImportPipeline\ImportInstagram;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait Instagram
{
    public function instagram()
    {
        if (config_cache('pixelfed.import.instagram.enabled') != true) {
            abort(404, 'Feature not enabled');
        }

        return view('settings.import.instagram.home');
    }

    public function instagramStart(Request $request)
    {
        $completed = ImportJob::whereProfileId(Auth::user()->profile->id)
            ->whereService('instagram')
            ->whereNotNull('completed_at')
            ->exists();
        if ($completed == true) {
            return redirect(route('settings'))->with(['error' => 'You can only import from Instagram once during the beta. Please report any issues!']);
        }
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
        if ($exists) {
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
        $max = 'max:'.config('pixelfed.import.instagram.limits.size');
        $this->validate($request, [
            'media.*' => 'required|mimes:bin,jpeg,png,gif|'.$max,
            //'mediajson' => 'required|file|mimes:json'
        ]);
        $media = $request->file('media');

        $profile = Auth::user()->profile;
        $job = ImportJob::whereProfileId($profile->id)
            ->whereNull('completed_at')
            ->whereUuid($uuid)
            ->whereStage(1)
            ->firstOrFail();

        $limit = config('pixelfed.import.instagram.limits.posts');
        foreach ($media as $k => $v) {
            $original = $v->getClientOriginalName();
            if (strlen($original) < 32 || $k > $limit) {
                continue;
            }
            $storagePath = "import/{$job->uuid}";
            $path = $v->storePublicly($storagePath);
            DB::transaction(function () use ($profile, $job, $path, $original) {
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
        DB::transaction(function () use ($job) {
            $job->stage = 2;
            $job->save();
        });

        return redirect($job->url());
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
            'media' => 'required|file|max:1000',
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
        if (! $json || ! isset($json['photos'])) {
            return abort(500);
        }
        $storagePath = "import/{$job->uuid}";
        $path = $media->storePublicly($storagePath);
        $job->media_json = $path;
        $job->stage = 3;
        $job->save();

        return redirect($job->url());
    }

    public function instagramStepThree(Request $request, $uuid)
    {
        $profile = Auth::user()->profile;
        $job = ImportJob::whereProfileId($profile->id)
            ->whereService('instagram')
            ->whereNull('completed_at')
            ->whereUuid($uuid)
            ->whereStage(3)
            ->firstOrFail();

        return view('settings.import.instagram.step-three', compact('profile', 'job'));
    }

    public function instagramStepThreeStore(Request $request, $uuid)
    {
        $profile = Auth::user()->profile;

        try {
            $import = ImportJob::whereProfileId($profile->id)
                ->where('uuid', $uuid)
                ->whereNotNull('media_json')
                ->whereNull('completed_at')
                ->whereStage(3)
                ->firstOrFail();
            ImportInstagram::dispatch($import);
        } catch (Exception $e) {
            \Log::info($e);
        }

        return redirect(route('settings'))->with(['status' => 'Import successful! It may take a few minutes to finish.']);
    }
}
