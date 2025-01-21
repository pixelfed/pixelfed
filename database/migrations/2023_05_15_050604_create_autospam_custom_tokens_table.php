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
        Schema::create('autospam_custom_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token')->index();
            $table->integer('weight')->default(1)->index();
            $table->boolean('is_spam')->default(true)->index();
            $table->text('note')->nullable();
            $table->string('category')->nullable()->index();
            $table->boolean('active')->default(false)->index();
            $table->unique(['token', 'category']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autospam_custom_tokens');
    }
};
