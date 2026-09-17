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
        Schema::create('feature_authorizations', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('profile_id')->index();
            $table->unsignedBigInteger('actor_id')->index();
            $table->string('collection_url', 500);
            $table->string('collection_name', 200)->nullable();
            $table->string('request_url', 500)->nullable();
            $table->string('state', 20)->default('approved')->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['profile_id', 'collection_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_authorizations');
    }
};
