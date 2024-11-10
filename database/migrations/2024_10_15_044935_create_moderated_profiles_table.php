<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Profile;
use App\ModLog;
use App\Models\ModeratedProfile;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('moderated_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('profile_url')->unique()->nullable()->index();
            $table->unsignedBigInteger('profile_id')->unique()->nullable();
            $table->string('domain')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_banned')->default(false);
            $table->boolean('is_nsfw')->default(false);
            $table->boolean('is_unlisted')->default(false);
            $table->boolean('is_noautolink')->default(false);
            $table->boolean('is_nodms')->default(false);
            $table->boolean('is_notrending')->default(false);
            $table->timestamps();
        });

        $logs = ModLog::whereObjectType('App\Profile::class')->whereAction('admin.user.delete')->get();

        foreach($logs as $log) {
            $profile = Profile::withTrashed()->find($log->object_id);
            if(!$profile || $profile->private_key) {
                continue;
            }
            ModeratedProfile::updateOrCreate([
                'profile_url' => $profile->remote_url,
                'profile_id' => $profile->id,
            ], [
                'is_banned' => true,
                'domain' => $profile->domain,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderated_profiles');
    }
};
