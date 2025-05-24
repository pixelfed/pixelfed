<?php

namespace App\Services;

use App\Media;
use App\Transformer\Api\MediaTransformer;
use Cache;
use Illuminate\Support\Arr;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class MediaService
{
    const CACHE_KEY = 'status:transformer:media:attachments:';

    public static function get($statusId)
    {
        return Cache::remember(self::CACHE_KEY.$statusId, 21600, function () use ($statusId) {
            $media = Media::whereStatusId($statusId)->orderBy('order')->get();
            if (! $media) {
                return [];
            }
            $fractal = new Fractal\Manager;
            $fractal->setSerializer(new ArraySerializer);
            $resource = new Fractal\Resource\Collection($media, new MediaTransformer);

            return $fractal->createData($resource)->toArray();
        });
    }

    public static function getMastodon($id)
    {
        $media = self::get($id);
        if (! $media) {
            return [];
        }
        $medias = collect($media)
            ->map(function ($media) {
                $mime = $media['mime'] ? explode('/', $media['mime']) : false;
                unset(
                    $media['optimized_url'],
                    $media['license'],
                    $media['is_nsfw'],
                    $media['orientation'],
                    $media['filter_name'],
                    $media['filter_class'],
                    $media['mime'],
                    $media['hls_manifest']
                );

                $media['type'] = $mime ? strtolower($mime[0]) : 'unknown';

                return $media;
            })
            ->filter(function ($m) {
                return $m && isset($m['url']);
            })
            ->values();

        return $medias->toArray();
    }

    public static function del($statusId)
    {
        return Cache::forget(self::CACHE_KEY.$statusId);
    }

    public static function activitypub($statusId, $fresh = false)
    {
        if ($fresh) {
            self::del($statusId);
        }

        $status = self::get($statusId);
        if (! $status) {
            return [];
        }

        return collect($status)->map(function ($s) {
            $original = Arr::get($s, 'meta.original', []);
            $mime = $s['mime'] === 'image/jpg' ? 'image/jpeg' : $s['mime'];

            return [
                'type' => 'Document',
                'mediaType' => $mime,
                'url' => $s['url'],
                'name' => $s['description'],
                'blurhash' => $s['blurhash'],
                'focalPoint' => [0, 0],
                'width' => $original['width'] ?? null,
                'height' => $original['height'] ?? null,
            ];
        });
    }
}
