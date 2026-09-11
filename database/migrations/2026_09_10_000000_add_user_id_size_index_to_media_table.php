<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $table = 'media';

    protected string $index = 'media_user_id_size_index';

    protected array $columns = ['user_id', 'size'];

    /**
     * Add a covering index for per-user storage aggregation.
     *
     * `SUM(size) WHERE user_id = ?` (UserStorageService::calculateStorageUsed)
     * previously required a full table scan because media.user_id was not
     * indexed. The composite (user_id, size) lets the aggregate be served
     * entirely from the index. On MySQL/MariaDB this uses INPLACE/LOCK=NONE so
     * it does not block writes on large instances; other drivers use the
     * portable schema builder.
     */
    public function up(): void
    {
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
