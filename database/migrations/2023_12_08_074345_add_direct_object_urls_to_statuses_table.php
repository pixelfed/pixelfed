<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Status;
use Illuminate\Database\UniqueConstraintViolationException;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach(Status::whereScope('direct')->whereNotNull('url')->whereNull('object_url')->lazyById(50, 'id') as $status) {
            try {
                $status->object_url = $status->url;
                $status->uri = $status->url;
                $status->save();
            } catch (Exception | UniqueConstraintViolationException $e) {
                continue;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
