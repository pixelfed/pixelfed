<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('places', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug')->index();
            $table->string('name')->index();
            $table->string('country')->index();
            $table->json('aliases')->nullable();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('long', 9, 6)->nullable();
            $table->unique(['slug', 'country', 'lat', 'long']);
            $table->timestamps();
        });

        Schema::table('statuses', function (Blueprint $table) {
            $table->bigInteger('place_id')->unsigned()->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('places');
        Schema::table('statuses', function (Blueprint $table) {
            $table->dropColumn('place_id');
        });
    }
}
