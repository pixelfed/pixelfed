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
        Schema::create('custom_filter_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_filter_id')->constrained()->onDelete('cascade');
            $table->string('keyword', 255)->nullable(false);
            $table->boolean('whole_word')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_filter_keywords');
    }
};
