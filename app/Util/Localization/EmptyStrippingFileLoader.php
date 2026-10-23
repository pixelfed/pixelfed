<?php

namespace App\Util\Localization;

use Illuminate\Translation\FileLoader;

/**
 * Translation loader that removes empty string values after loading.
 *
 * Crowdin exports untranslated keys as empty strings ('') rather than
 * omitting them. Laravel's translator only falls back to the fallback
 * locale when a key is entirely missing, not when it resolves to an empty
 * string, so partially-translated locales would render blank labels.
 *
 * Stripping empty values here makes those keys "missing", which restores
 * the expected fallback to the fallback locale (en-US).
 */
class EmptyStrippingFileLoader extends FileLoader
{
    /**
     * Load the messages for the given locale/group, minus empty strings.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string|null  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        $messages = parent::load($locale, $group, $namespace);

        return $this->stripEmptyStrings($messages);
    }

    /**
     * Recursively remove empty (or whitespace-only) string values.
     */
    protected function stripEmptyStrings(array $messages): array
    {
        $result = [];

        foreach ($messages as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->stripEmptyStrings($value);
            } elseif (is_string($value)) {
                if (trim($value) !== '') {
                    $result[$key] = $value;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
