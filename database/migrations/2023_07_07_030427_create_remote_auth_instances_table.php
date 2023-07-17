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
        Schema::create('remote_auth_instances', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->nullable()->unique()->index();
            $table->unsignedInteger('instance_id')->nullable()->index();
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->string('redirect_uri')->nullable();
            $table->string('root_domain')->nullable()->index();
            $table->boolean('allowed')->nullable()->index();
            $table->boolean('banned')->default(false)->index();
            $table->boolean('active')->default(true)->index();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remote_auth_instances');
    }
};
