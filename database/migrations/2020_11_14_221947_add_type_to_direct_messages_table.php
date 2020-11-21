<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToDirectMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->string('type')->default('text')->nullable()->index()->after('from_id');
            $table->boolean('is_hidden')->default(false)->index()->after('group_message');
            $table->json('meta')->nullable()->after('is_hidden');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('is_hidden');
            $table->dropColumn('meta');
        });
    }
}
