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
                $sql = <<<'SQL'
# rename the existing type
ALTER TYPE visibility_enum RENAME TO visibility_enum_old;

# create the new type
CREATE TYPE visibility_enum AS ENUM('public','unlisted','private','direct', 'draft');

# update the columns to use the new type
ALTER TABLE statuses ALTER COLUMN visibility TYPE visibility_enum USING visibility::text::visibility_enum;

# remove the old type
DROP TYPE visibility_enum_old;
SQL;
                DB::statement($sql);
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
