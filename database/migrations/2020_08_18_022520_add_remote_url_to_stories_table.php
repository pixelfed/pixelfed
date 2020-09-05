<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemoteUrlToStoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->string('remote_url')->nullable()->index()->unique()->after('path');
            $table->string('media_url')->nullable()->index()->unique()->after('remote_url');
            $table->boolean('is_archived')->default(false)->nullable()->index();
            $table->string('name')->nullable();
        });
        Schema::table('media', function (Blueprint $table) {
            $table->string('blurhash')->nullable();
            $table->json('srcset')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('remote_url');
            $table->dropColumn('media_url');
            $table->dropColumn('is_archived');
            $table->dropColumn('name');
        });
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('blurhash');
            $table->dropColumn('srcset');
            $table->dropColumn('width');
            $table->dropColumn('height');
        });
    }
}
