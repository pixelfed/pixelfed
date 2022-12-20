<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_invites', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('invite_code')->unique()->index();
            $table->text('description')->nullable();
            $table->text('message')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses')->nullable();
            $table->boolean('skip_email_verification')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->json('used_by')->nullable();
            $table->unsignedInteger('admin_user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admin_invites');
    }
};
