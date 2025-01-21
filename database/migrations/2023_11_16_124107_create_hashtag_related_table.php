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
        Schema::create('hashtag_related', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('hashtag_id')->unsigned()->unique()->index();
            $table->json('related_tags')->nullable();
            $table->bigInteger('agg_score')->unsigned()->nullable()->index();
            $table->timestamp('last_calculated_at')->nullable()->index();
            $table->timestamp('last_moderated_at')->nullable()->index();
            $table->boolean('skip_refresh')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hashtag_related');
    }
};
