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
        Schema::create('curated_register_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('register_id')->nullable()->index();
            $table->unsignedInteger('admin_id')->nullable();
            $table->unsignedInteger('reply_to_id')->nullable()->index();
            $table->string('secret_code')->nullable();
            $table->string('type')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('link')->nullable();
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('from_admin')->default(false)->index();
            $table->boolean('from_user')->default(false)->index();
            $table->boolean('admin_only_view')->default(true);
            $table->boolean('action_required')->default(false);
            $table->timestamp('admin_notified_at')->nullable();
            $table->timestamp('action_taken_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curated_register_activities');
    }
};
