<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateStatusVisibilityDefaults extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $type = config('database.default');
        switch($type)
        {
            case 'mysql':
                DB::statement("ALTER TABLE statuses CHANGE COLUMN visibility visibility ENUM('public','unlisted','private','direct', 'draft') NOT NULL DEFAULT 'public'");
                break;

            case 'pgsql':
                DB::statement("ALTER TYPE visibility_enum RENAME TO visibility_enum_old;");
                DB::statement("CREATE TYPE visibility_enum AS ENUM('public','unlisted','private','direct', 'draft');");
                DB::statement("ALTER TABLE statuses ALTER COLUMN visibility TYPE visibility_enum USING visibility::text::visibility_enum;");
                DB::statement("DROP TYPE visibility_enum_old;");
                break;
        }
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
}
