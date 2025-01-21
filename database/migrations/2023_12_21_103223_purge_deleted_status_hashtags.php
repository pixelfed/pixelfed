<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\StatusHashtag;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        StatusHashtag::doesntHave('status')->lazyById(200)->each->deleteQuietly();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
