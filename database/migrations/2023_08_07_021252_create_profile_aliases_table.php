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
        Schema::create('profile_aliases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id')->nullable()->index();
            $table->string('acct')->nullable();
            $table->string('uri')->nullable();
            $table->foreign('profile_id')->references('id')->on('profiles');
            $table->unique(['profile_id', 'acct'], 'profile_id_acct_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_aliases');
    }
};
