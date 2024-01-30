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
        Schema::create('parental_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('parent_id')->index();
            $table->unsignedInteger('child_id')->unique()->index()->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('verify_code')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->json('permissions')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('user_roles', function (Blueprint $table) {
            $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound  = $schemaManager->listTableIndexes('user_roles');
            if (array_key_exists('user_roles_profile_id_unique', $indexesFound)) {
                $table->dropUnique('user_roles_profile_id_unique');
            }
            $table->unsignedBigInteger('profile_id')->unique()->nullable()->index()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parental_controls');

        Schema::table('user_roles', function (Blueprint $table) {
            $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound  = $schemaManager->listTableIndexes('user_roles');
            if (array_key_exists('user_roles_profile_id_unique', $indexesFound)) {
                $table->dropUnique('user_roles_profile_id_unique');
            }
            $table->unsignedBigInteger('profile_id')->unique()->index()->change();
        });
    }
};
