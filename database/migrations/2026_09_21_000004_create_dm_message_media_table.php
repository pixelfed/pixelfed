<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_message_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('message_id')->index();
            $table->unsignedBigInteger('media_id')->unique();
            $table->unsignedTinyInteger('position')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_message_media');
    }
};
