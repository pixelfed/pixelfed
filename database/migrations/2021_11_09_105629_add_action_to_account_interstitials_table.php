<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActionToAccountInterstitialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_interstitials', function (Blueprint $table) {
        	$table->tinyInteger('severity_index')->unsigned()->nullable()->index();
            $table->boolean('is_spam')->nullable()->index()->after('item_type');
            $table->boolean('in_violation')->nullable()->index()->after('is_spam');
            $table->unsignedInteger('violation_id')->nullable()->index()->after('in_violation');
            $table->boolean('email_notify')->nullable()->index()->after('violation_id');
            $table->bigInteger('thread_id')->unsigned()->unique()->nullable();
            $table->timestamp('emailed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_interstitials', function (Blueprint $table) {
            $table->dropColumn('severity_index');
            $table->dropColumn('is_spam');
            $table->dropColumn('in_violation');
            $table->dropColumn('violation_id');
            $table->dropColumn('email_notify');
            $table->dropColumn('thread_id');
            $table->dropColumn('emailed_at');
        });
    }
}
