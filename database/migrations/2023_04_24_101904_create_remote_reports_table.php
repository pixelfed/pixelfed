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
        Schema::create('remote_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->json('status_ids')->nullable();
            $table->text('comment')->nullable();
            $table->bigInteger('account_id')->unsigned()->nullable();
            $table->string('uri')->nullable();
            $table->unsignedInteger('instance_id')->nullable();
            $table->timestamp('action_taken_at')->nullable()->index();
            $table->json('report_meta')->nullable();
            $table->json('action_taken_meta')->nullable();
            $table->bigInteger('action_taken_by_account_id')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remote_reports');
    }
};
