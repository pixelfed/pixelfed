<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountInterstitialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_interstitials', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('type')->nullable();
            $table->string('view')->nullable();
            $table->bigInteger('item_id')->unsigned()->nullable();
            $table->string('item_type')->nullable();
            $table->boolean('has_media')->default(false)->nullable();
            $table->string('blurhash')->nullable();
            $table->text('message')->nullable();
            $table->text('violation_header')->nullable();
            $table->text('violation_body')->nullable();
            $table->json('meta')->nullable();
            $table->text('appeal_message')->nullable();
            $table->timestamp('appeal_requested_at')->nullable()->index();
            $table->timestamp('appeal_handled_at')->nullable()->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('users', function(Blueprint $table) {
            $table->boolean('has_interstitial')->default(false)->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_interstitials');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('has_interstitial');
        });
    }
}
