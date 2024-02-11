<?php

use App\Status;
use Illuminate\Database\Migrations\Migration;

class AddMissingReblogOfIdTypesToStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Status::whereNotNull('reblog_of_id')
            ->whereNull('type')
            ->update([
                'type' => 'share',
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
