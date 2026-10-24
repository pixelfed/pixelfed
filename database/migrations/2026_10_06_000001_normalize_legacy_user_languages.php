<?php

use App\Util\Localization\Localization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rewrite legacy short language codes stored on users (es, fr, de, ...)
     * to their current locale-coded equivalents (es-ES, fr-FR, de-DE, ...).
     *
     * The lang/ directories and the Vue i18n message bundles were renamed to
     * regional codes, so a user still holding a short code falls back to
     * English and cannot match the language picker. Each legacy value is
     * updated in place; rows already using a regional code are left untouched.
     */
    public function up(): void
    {
        foreach (Localization::LEGACY_LOCALE_MAP as $legacy => $current) {
            DB::table('users')
                ->where('language', $legacy)
                ->update(['language' => $current]);
        }
    }
};
