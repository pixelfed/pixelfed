<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('domain')->nullable();
            $table->string('username')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('bio', 150)->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->text('keybase_proof')->nullable();
            $table->boolean('is_private')->default(false);
            // ActivityPub
            $table->string('sharedInbox')->nullable()->index();
            // PuSH/WebSub
            $table->string('verify_token')->nullable();
            $table->string('secret')->nullable();
            // RSA Key Pair
            $table->text('private_key')->nullable();
            $table->text('public_key')->nullable();
            // URLs
            $table->string('remote_url')->nullable();
            $table->string('salmon_url')->nullable();
            $table->string('hub_url')->nullable();
            $table->unique(['domain', 'username']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profiles');
    }
}
