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
        Schema::create('curated_registers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique()->nullable()->index();
            $table->string('username')->unique()->nullable()->index();
            $table->string('password')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('verify_code')->nullable();
            $table->text('reason_to_join')->nullable();
            $table->unsignedBigInteger('invited_by')->nullable()->index();
            $table->boolean('is_approved')->default(0)->index();
            $table->boolean('is_rejected')->default(0)->index();
            $table->boolean('is_awaiting_more_info')->default(0)->index();
            $table->boolean('is_closed')->default(0)->index();
            $table->json('autofollow_account_ids')->nullable();
            $table->json('admin_notes')->nullable();
            $table->unsignedInteger('approved_by_admin_id')->nullable();
            $table->timestamp('email_verified_at')->nullable();
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
        Schema::dropIfExists('curated_registers');
    }
};
