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
        Schema::create('quote_authorizations', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('profile_id')->index();
            $table->unsignedBigInteger('status_id')->index();
            $table->unsignedBigInteger('actor_id')->index();
            $table->string('quote_url', 500);
            $table->string('request_url', 500)->nullable();
            $table->string('state', 20)->default('approved')->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['status_id', 'quote_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_authorizations');
    }
};
