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
            $table->dropColumn('compose_settings');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->string('caption')->change();
            $table->dropIndex('profile_id');
            $table->dropIndex('mime');
            $table->dropIndex('license');
        });
    }
}
