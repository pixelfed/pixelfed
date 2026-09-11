<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $table = 'notifications';

    protected string $index = 'notifications_profile_deleted_id_index';

    protected array $columns = ['profile_id', 'deleted_at', 'id'];

    public function up(): void
    {
        // MySQL/MariaDB: use online DDL (INPLACE/LOCK=NONE) so index creation
        // does not block writes on large instances. Other drivers (pgsql,
        // sqlite) use the portable schema builder, which emits correct
        // dialect-specific SQL.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $columns = collect($this->columns)
                ->map(fn ($column) => "`{$column}`")
                ->implode(', ');

            DB::statement("
                ALTER TABLE `{$this->table}`
                ADD INDEX `{$this->index}` ({$columns}),
                ALGORITHM=INPLACE,
                LOCK=NONE
            ");

            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            $table->index($this->columns, $this->index);
        });
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("
                ALTER TABLE `{$this->table}`
                DROP INDEX `{$this->index}`,
                ALGORITHM=INPLACE,
                LOCK=NONE
            ");

            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            $table->dropIndex($this->index);
        });
    }
};
