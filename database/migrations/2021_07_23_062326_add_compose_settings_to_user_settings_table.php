<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddComposeSettingsToUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->json('compose_settings')->nullable();
        });

        Schema::table('media', function (Blueprint $table) {
        	$table->text('caption')->change();
        	$table->index('profile_id');
        	$table->index('mime');
        	$table->index('license');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_settings', function (Blueprint $table) {
            if (Schema::hasColumn('user_settings', 'compose_settings')) {
                $table->dropColumn('compose_settings');
            }
        });

        Schema::table('media', function (Blueprint $table) {
            $table->string('caption')->change();

            $indexes = Schema::getIndexes('media');
            $indexesFound = collect($indexes)->map(function($i) { return $i['name']; })->toArray();
            if (in_array('media_profile_id_index', $indexesFound)) {
                $table->dropIndex('media_profile_id_index');
            }
            if (in_array('media_mime_index', $indexesFound)) {
                $table->dropIndex('media_mime_index');
            }
            if (in_array('media_license_index', $indexesFound)) {
                $table->dropIndex('media_license_index');
            }
        });
    }
}
