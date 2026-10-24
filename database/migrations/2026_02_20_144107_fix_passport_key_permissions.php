<?php

use Illuminate\Database\Migrations\Migration;

class FixPassportKeyPermissions extends Migration
{
    public function up(): void
    {
        $keys = [
            storage_path('oauth-private.key'),
            storage_path('oauth-public.key'),
        ];

        foreach ($keys as $key) {
            if (file_exists($key)) {
                chmod($key, 0660);
            }
        }
    }

    public function down(): void
    {
        $keys = [
            storage_path('oauth-private.key'),
            storage_path('oauth-public.key'),
        ];

        foreach ($keys as $key) {
            if (file_exists($key)) {
                chmod($key, 0660);
            }
        }
    }
}
