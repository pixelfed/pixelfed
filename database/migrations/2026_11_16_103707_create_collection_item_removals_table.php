<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_item_removals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('status_id')->index();
            $table->unsignedBigInteger('profile_id')->index();
            $table->timestamps();

            $table->unique(['collection_id', 'status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_item_removals');
    }
};
