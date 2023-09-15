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
        Schema::create('admin_shadow_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->morphs('item');
            $table->boolean('is_local')->default(true)->index();
            $table->text('note')->nullable();
            $table->boolean('active')->default(false)->index();
            $table->json('history')->nullable();
            $table->json('ruleset')->nullable();
            $table->boolean('prevent_ap_fanout')->default(false)->index();
            $table->boolean('prevent_new_dms')->default(false)->index();
            $table->boolean('ignore_reports')->default(false)->index();
            $table->boolean('ignore_mentions')->default(false)->index();
            $table->boolean('ignore_links')->default(false)->index();
            $table->boolean('ignore_hashtags')->default(false)->index();
            $table->boolean('hide_from_public_feeds')->default(false)->index();
            $table->boolean('hide_from_tag_feeds')->default(false)->index();
            $table->boolean('hide_embeds')->default(false)->index();
            $table->boolean('hide_from_story_carousel')->default(false)->index();
            $table->boolean('hide_from_search_autocomplete')->default(false)->index();
            $table->boolean('hide_from_search')->default(false)->index();
            $table->boolean('requires_login')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_shadow_filters');
    }
};
