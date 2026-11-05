<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_content_credentials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('media_id')->unique();
            $table->string('source', 8)->default('local');
            $table->string('state', 8)->nullable();
            $table->string('signer')->nullable();
            $table->string('generator')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('digital_source_type', 64)->nullable();
            $table->string('ai_label', 16)->nullable()->index();
            $table->json('actions')->nullable();
            $table->timestamp('instance_signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_content_credentials');
    }
};
