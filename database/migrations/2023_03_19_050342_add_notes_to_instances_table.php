<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->text('notes')->nullable();
            $table->boolean('manually_added')->default(false);
            $table->string('base_domain')->nullable();
            $table->boolean('ban_subdomains')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->boolean('list_limitation')->default(false)->index();
            $table->index('banned');
            $table->index('auto_cw');
            $table->index('unlisted');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->dropColumn('notes');
            $table->dropColumn('manually_added');
            $table->dropColumn('base_domain');
            $table->dropColumn('ban_subdomains');
            $table->dropColumn('ip_address');
            $table->dropColumn('list_limitation');
            $table->dropIndex('instances_banned_index');
            $table->dropIndex('instances_auto_cw_index');
            $table->dropIndex('instances_unlisted_index');
        });
    }
};
