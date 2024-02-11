<?php

use App\StatusHashtag;
use Illuminate\Database\Migrations\Migration;

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
