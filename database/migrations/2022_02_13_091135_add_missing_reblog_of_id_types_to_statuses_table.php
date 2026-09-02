<?php

use App\Models\Status;
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
    public function down() {}
};
