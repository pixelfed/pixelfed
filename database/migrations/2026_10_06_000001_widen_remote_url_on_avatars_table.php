<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('avatars'))->pluck('name')->all();

        if (in_array('avatars_remote_url_index', $indexes)) {
            Schema::table('avatars', function (Blueprint $table) {
                $table->dropIndex('avatars_remote_url_index');
            });
        }

        Schema::table('avatars', function (Blueprint $table) {
            $table->string('remote_url', 2048)->nullable()->change();
        });
    }

    public function down(): void {}
};
