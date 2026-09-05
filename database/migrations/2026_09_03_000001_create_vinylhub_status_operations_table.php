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
        Schema::create('vinyl_hub_status_operations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('profile_id')->index();
            $table->string('operation_key', 255);
            $table->string('state', 32)->default('incomplete')->index();
            $table->unsignedBigInteger('status_id')->nullable()->index();
            $table->string('status_url')->nullable();
            $table->timestamps();

            $table->unique(['profile_id', 'operation_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vinyl_hub_status_operations');
    }
};
