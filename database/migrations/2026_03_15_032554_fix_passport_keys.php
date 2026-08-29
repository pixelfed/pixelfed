<?php

use Illuminate\Database\Migrations\Migration;
use Laravel\Passport\Passport;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! windows_os()) {
            if (file_exists(Passport::keyPath('oauth-public.key'))) {
                chmod(Passport::keyPath('oauth-public.key'), 0660);
            }

            if (file_exists(Passport::keyPath('oauth-private.key'))) {
                chmod(Passport::keyPath('oauth-private.key'), 0600);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
