<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupBlocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('group_id')->unsigned()->index();
			$table->bigInteger('admin_id')->unsigned()->nullable();
			$table->bigInteger('profile_id')->nullable()->unsigned()->index();
			$table->bigInteger('instance_id')->nullable()->unsigned()->index();
			$table->string('name')->nullable()->index();
			$table->string('reason')->nullable();
			$table->boolean('is_user')->index();
			$table->boolean('moderated')->default(false)->index();
			$table->unique(['group_id', 'profile_id', 'instance_id']);
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
        Schema::dropIfExists('group_blocks');
    }
}
