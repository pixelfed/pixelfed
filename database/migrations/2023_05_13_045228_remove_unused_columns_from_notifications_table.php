<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
        	if(Schema::hasColumn('notifications', 'message')) {
            	$table->dropColumn('message');
        	}

        	if(Schema::hasColumn('notifications', 'rendered')) {
            	$table->dropColumn('rendered');
        	}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
