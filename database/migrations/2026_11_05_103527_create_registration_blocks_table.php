<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_blocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 16);
            $table->string('match_type', 16)->default('exact');
            $table->string('pattern', 255);
            $table->string('note', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->unique(['type', 'match_type', 'pattern']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_blocks');
    }
};
