<?php

use App\Jobs\StatusPipeline\StatusDelete;
use App\Status;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Status::doesntHave('profile')->get()->each(function ($status) {
            StatusDelete::dispatch($status);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
