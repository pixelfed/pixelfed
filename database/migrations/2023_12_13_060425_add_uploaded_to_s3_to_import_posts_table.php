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
        Schema::table('import_posts', function (Blueprint $table) {
            $table->boolean('uploaded_to_s3')->default(false)->index()->after('skip_missing_media');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_posts', function (Blueprint $table) {
            $table->dropColumn('uploaded_to_s3');
        });
    }
};
