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
	Schema::create('owa_verifications', function (Blueprint $table) {
		$table->string('token')->index();
		$table->string('remote_url');
		$table->timestamp('created_at');
	});	
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owa_verifications'); 
    }
};