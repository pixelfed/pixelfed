<?php

namespace App\Services\ContentCredentials;

class DigitalSourceType
{
    public const BASE = 'http://cv.iptc.org/newscodes/digitalsourcetype/';

    public const LABEL_AI_GENERATED = 'ai_generated';

    public const LABEL_AI_MODIFIED = 'ai_modified';

    public const KNOWN = [
        'digitalCapture',
        'computationalCapture',
        'negativeFilm',
        'positiveFilm',
        'print',
        'humanEdits',
        'compositeCapture',
        'algorithmicallyEnhanced',
        'dataDrivenMedia',
        'digitalArt',
        'virtualRecording',
        'compositeSynthetic',
        'trainedAlgorithmicMedia',
        'compositeWithTrainedAlgorithmicMedia',
        'algorithmicMedia',
        'screenCapture',
        'digitalCreation',
        'minorHumanEdits',
    ];

    public const AI = [
        'trainedAlgorithmicMedia',
        'compositeWithTrainedAlgorithmicMedia',
    ];

    public static function term(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || strlen($value) > 255) {
            return null;
        }

        $value = rtrim($value, '/');
        $pos = strrpos($value, '/');
        $term = $pos === false ? $value : substr($value, $pos + 1);

        if ($pos !== false && ! preg_match('#^https?://cv\.iptc\.org/newscodes/digitalsourcetype$#i', substr($value, 0, $pos))) {
            return null;
        }

        return in_array($term, self::KNOWN, true) ? $term : null;
    }

    public static function uri(?string $term): ?string
    {
        return $term !== null && in_array($term, self::KNOWN, true) ? self::BASE.$term : null;
    }

    public static function isAi(?string $term): bool
    {
        return $term !== null && in_array($term, self::AI, true);
    }

    public static function labelFor(?string $term): ?string
    {
        return match ($term) {
            'trainedAlgorithmicMedia' => self::LABEL_AI_GENERATED,
            'compositeWithTrainedAlgorithmicMedia' => self::LABEL_AI_MODIFIED,
            default => null,
        };
    }
}
