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
        Schema::create('remote_auths', function (Blueprint $table) {
            $table->id();
            $table->string('software')->nullable();
            $table->string('domain')->nullable()->index();
            $table->string('webfinger')->nullable()->unique()->index();
            $table->unsignedInteger('instance_id')->nullable()->index();
            $table->unsignedInteger('user_id')->nullable()->unique()->index();
            $table->unsignedInteger('client_id')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->text('bearer_token')->nullable();
            $table->json('verify_credentials')->nullable();
            $table->timestamp('last_successful_login_at')->nullable();
            $table->timestamp('last_verify_credentials_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remote_auths');
    }
};
