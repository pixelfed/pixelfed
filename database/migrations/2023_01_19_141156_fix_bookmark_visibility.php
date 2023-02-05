<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Bookmark;
use App\Status;
use App\Services\FollowerService;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Bookmark::chunk(200, function($bookmarks) {
            foreach($bookmarks as $bookmark) {
                $status = Status::find($bookmark->status_id);
                if(!$status) {
                    $bookmark->delete();
                    continue;
                }

                if(!in_array($status->visibility, ['public', 'unlisted', 'private'])) {
                    $bookmark->delete();
                    continue;
                }

                if(!in_array($status->visibility, ['public', 'unlisted'])) {
                    if($bookmark->profile_id == $status->profile_id) {
                        continue;
                    } else {
                        if(!FollowerService::follows($bookmark->profile_id, $status->profile_id)) {
                            $bookmark->delete();
                        }
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
