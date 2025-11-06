<?php

namespace App\Jobs\StoryPipeline;

use App\Services\MediaPathService;
use App\Services\StoryIndexService;
use App\Services\StoryService;
use App\Story;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\Validator\StoryValidator;
use App\Util\Lexer\Bearcap;
use Cache;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\File;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Log;

class StoryFetch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $activity;

    private const MAX_DURATION = 300;

    private const REQUEST_TIMEOUT = 30;

    private const MAX_REDIRECTS = 3;

    // Rate limiting
    public $tries = 3;

    public $maxExceptions = 2;

    public $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     */
    public function __construct($activity)
    {
        $this->activity = $activity;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if (config('app.dev_log')) {
            Log::info('StoryFetch job started', ['activity_id' => $this->activity['id'] ?? 'unknown']);
        }

        try {
            $this->processStoryFetch();
        } catch (Exception $e) {
            if (config('app.dev_log')) {
                Log::error('StoryFetch job failed', [
                    'activity_id' => $this->activity['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            throw $e;
        }
    }

    /**
     * Main processing logic
     */
    private function processStoryFetch()
    {
        if (! $this->validateActivityStructure()) {
            if (config('app.dev_log')) {
                Log::warning('Invalid activity structure', ['activity' => $this->activity]);
            }

            return;
        }

        $activity = $this->activity;
        $activityId = $activity['id'];
        $activityActor = $activity['actor'];

        if (! $this->validateDomainConsistency($activityId, $activityActor)) {
            if (config('app.dev_log')) {
                Log::warning('Domain mismatch detected', [
                    'activity_id' => $activityId,
                    'actor' => $activityActor,
                ]);
            }

            return;
        }

        // Rate limiting check
        if ($this->isRateLimited($activityActor)) {
            if (config('app.dev_log')) {
                Log::info('Rate limited', ['actor' => $activityActor]);
            }
            $this->release(3600); // Retry in 1 hour

            return;
        }

        // Decode and validate bearcap token
        $bearcap = $this->validateBearcap($activity['object']['object'] ?? null);
        if (! $bearcap) {
            return;
        }

        $url = $bearcap['url'];
        $token = $bearcap['token'];

        // Additional domain validation for bearcap URL
        if (! $this->validateDomainConsistency($activityId, $url)) {
            if (config('app.dev_log')) {
                Log::warning('Bearcap URL domain mismatch', [
                    'activity_id' => $activityId,
                    'bearcap_url' => $url,
                ]);
            }

            return;
        }

        // Fetch and validate story data
        $payload = $this->fetchStoryPayload($url, $token);
        if (! $payload) {
            return;
        }

        // Validate payload structure and security
        if (! $this->validatePayload($payload)) {
            return;
        }

        // Fetch and validate profile
        $profile = $this->fetchAndValidateProfile($payload['attributedTo']);
        if (! $profile) {
            return;
        }

        // Download and process media with security checks
        $mediaResult = $this->downloadAndValidateMedia($payload, $profile);
        if (! $mediaResult) {
            return;
        }

        // Create story record with transaction
        $this->createStoryRecord($payload, $profile, $mediaResult);
    }

    /**
     * Validate basic activity structure
     */
    private function validateActivityStructure(): bool
    {
        $validator = Validator::make($this->activity, [
            'id' => 'required|url|max:2000',
            'actor' => 'required|url|max:2000',
            'object.object' => 'required|string|max:1000',
        ]);

        return ! $validator->fails();
    }

    /**
     * Enhanced domain consistency validation
     */
    private function validateDomainConsistency(string $url1, string $url2): bool
    {
        $host1 = parse_url($url1, PHP_URL_HOST);
        $host2 = parse_url($url2, PHP_URL_HOST);

        if (! $host1 || ! $host2) {
            return false;
        }

        // Normalize hosts (remove www prefix if present)
        $host1 = ltrim(strtolower($host1), 'www.');
        $host2 = ltrim(strtolower($host2), 'www.');

        return $host1 === $host2;
    }

    /**
     * Rate limiting check
     */
    private function isRateLimited(string $actor): bool
    {
        $domain = parse_url($actor, PHP_URL_HOST);
        $cacheKey = "story_fetch_rate_limit:{$domain}";
        $currentCount = Cache::get($cacheKey, 0);

        // Allow 5000 story fetches per hour per domain
        if ($currentCount >= 5000) {
            return true;
        }

        Cache::put($cacheKey, $currentCount + 1, 3600);

        return false;
    }

    /**
     * Enhanced bearcap validation
     */
    private function validateBearcap(?string $bearcapString): ?array
    {
        if (! $bearcapString) {
            if (config('app.dev_log')) {
                Log::warning('Empty bearcap string');
            }

            return null;
        }

        try {
            $bearcap = Bearcap::decode($bearcapString);

            if (! $bearcap || ! isset($bearcap['url'], $bearcap['token'])) {
                if (config('app.dev_log')) {
                    Log::warning('Invalid bearcap structure');
                }

                return null;
            }

            // Validate URL format
            if (! filter_var($bearcap['url'], FILTER_VALIDATE_URL)) {
                if (config('app.dev_log')) {
                    Log::warning('Invalid bearcap URL', ['url' => $bearcap['url']]);
                }

                return null;
            }

            // Validate token format (should be non-empty)
            if (empty($bearcap['token']) || strlen($bearcap['token']) < 10) {
                if (config('app.dev_log')) {
                    Log::warning('Invalid bearcap token');
                }

                return null;
            }

            return $bearcap;
        } catch (Exception $e) {
            if (config('app.dev_log')) {
                Log::warning('Bearcap decode failed', ['error' => $e->getMessage()]);
            }

            return null;
        }
    }

    /**
     * Enhanced story payload fetching with security
     */
    private function fetchStoryPayload(string $url, string $token): ?array
    {
        $version = config('pixelfed.version');
        $appUrl = config('app.url');

        $headers = [
            'Accept' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
            'Authorization' => 'Bearer '.$token,
            'User-Agent' => "(Pixelfed/{$version}; +{$appUrl})",
        ];

        try {
            $response = Http::withHeaders($headers)
                ->timeout(self::REQUEST_TIMEOUT)
                ->connectTimeout(10)
                ->retry(2, 1000)
                ->withOptions([
                    'verify' => true,
                    'max_redirects' => self::MAX_REDIRECTS,
                ])
                ->get($url);

            if (! $response->successful()) {
                if (config('app.dev_log')) {
                    Log::warning('Story fetch failed', [
                        'url' => $url,
                        'status' => $response->status(),
                    ]);
                }

                return null;
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                if (config('app.dev_log')) {
                    Log::warning('Invalid JSON payload received');
                }

                return null;
            }

            return $payload;

        } catch (RequestException|ConnectionException $e) {
            if (config('app.dev_log')) {
                Log::warning('HTTP request failed', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        } catch (Exception $e) {
            if (config('app.dev_log')) {
                Log::error('Unexpected error in story fetch', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        }
    }

    /**
     * Enhanced payload validation
     */
    private function validatePayload(array $payload): bool
    {
        if (! StoryValidator::validate($payload)) {
            if (config('app.dev_log')) {
                Log::warning('Story validator failed');
            }

            return false;
        }

        // Payload security validations
        $validator = Validator::make($payload, [
            'id' => 'required|url|max:2000',
            'attributedTo' => 'required|url|max:2000',
            'attachment.url' => 'required|url|max:2000',
            'attachment.type' => 'required|in:Image,Video',
            'attachment.mediaType' => 'required|string|max:100',
            'duration' => 'nullable|integer|min:0|max:'.self::MAX_DURATION,
            'published' => 'required|date',
            'expiresAt' => 'required|date|after:published',
            'can_reply' => 'boolean',
            'can_react' => 'boolean',
        ]);

        if ($validator->fails()) {
            if (config('app.dev_log')) {
                Log::warning('Payload validation failed', ['errors' => $validator->errors()]);
            }

            return false;
        }

        // Validate media URL
        if (! Helpers::validateUrl($payload['attachment']['url'])) {
            if (config('app.dev_log')) {
                Log::warning('Invalid attachment URL');
            }

            return false;
        }

        // Validate MIME type
        $mimeType = $payload['attachment']['mediaType'];
        $allowedMimeTypes = $this->getAllowedMimeTypes();

        if (! in_array($mimeType, $allowedMimeTypes)) {
            if (config('app.dev_log')) {
                Log::warning('Invalid MIME type', [
                    'mime' => $mimeType,
                    'type' => $payload['attachment']['type'],
                    'allowed' => $allowedMimeTypes,
                ]);
            }

            return false;
        }

        return true;
    }

    /**
     * Enhanced profile fetching with validation
     */
    private function fetchAndValidateProfile(string $attributedTo)
    {
        try {
            $profile = Helpers::profileFetch($attributedTo);

            if (! $profile || ! $profile->id) {
                if (config('app.dev_log')) {
                    Log::warning('Profile fetch failed', ['attributed_to' => $attributedTo]);
                }

                return null;
            }

            // Check if profile is blocked or suspended
            if ($profile->status !== null && in_array($profile->status, ['suspended', 'deleted'])) {
                if (config('app.dev_log')) {
                    Log::info('Profile is suspended/deleted', ['profile_id' => $profile->id]);
                }

                return null;
            }

            return $profile;
        } catch (Exception $e) {
            if (config('app.dev_log')) {
                Log::error('Profile fetch error', [
                    'attributed_to' => $attributedTo,
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        }
    }

    /**
     * Enhanced media download with comprehensive security
     */
    private function downloadAndValidateMedia(array $payload, $profile): ?array
    {
        $mediaUrl = $payload['attachment']['url'];
        $ext = strtolower(pathinfo(parse_url($mediaUrl, PHP_URL_PATH), PATHINFO_EXTENSION));

        $allowedExtensions = $this->getAllowedExtensions();
        if (! in_array($ext, $allowedExtensions)) {
            if (config('app.dev_log')) {
                Log::warning('Invalid file extension', ['extension' => $ext, 'allowed' => $allowedExtensions]);
            }

            return null;
        }

        $fileName = $this->generateSecureFileName($ext);
        $storagePath = MediaPathService::story($profile);
        $tmpBase = storage_path('app/remcache/');
        $tmpPath = $profile->id.'-'.$fileName;
        $tmpName = $tmpBase.$tmpPath;

        if (! is_dir($tmpBase)) {
            mkdir($tmpBase, 0755, true);
        }

        try {
            $contextOptions = [
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peername' => true,
                    'allow_self_signed' => false,
                    'SNI_enabled' => true,
                ],
                'http' => [
                    'timeout' => self::REQUEST_TIMEOUT,
                    'max_redirects' => self::MAX_REDIRECTS,
                    'user_agent' => 'Pixelfed/'.config('pixelfed.version'),
                ],
            ];

            $ctx = stream_context_create($contextOptions);

            $data = $this->downloadWithSizeLimit($mediaUrl, $ctx);
            if (! $data) {
                return null;
            }

            if (file_put_contents($tmpName, $data) === false) {
                if (config('app.dev_log')) {
                    Log::error('Failed to write temp file', ['temp_name' => $tmpName]);
                }

                return null;
            }

            if (! $this->validateDownloadedFile($tmpName, $payload['attachment']['mediaType'])) {
                unlink($tmpName);

                return null;
            }

            $disk = Storage::disk(config('filesystems.default'));
            $path = $disk->putFileAs($storagePath, new File($tmpName), $fileName, 'public');
            $size = filesize($tmpName);

            unlink($tmpName);

            if (! $path) {
                if (config('app.dev_log')) {
                    Log::error('Failed to store file permanently');
                }

                return null;
            }

            return [
                'path' => $path,
                'size' => $size,
                'filename' => $fileName,
            ];

        } catch (Exception $e) {
            if (file_exists($tmpName)) {
                unlink($tmpName);
            }

            if (config('app.dev_log')) {
                Log::error('Media download failed', [
                    'url' => $mediaUrl,
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        }
    }

    /**
     * Download with size limit enforcement
     */
    private function downloadWithSizeLimit(string $url, $context): ?string
    {
        $maxFileSizeBytes = $this->getMaxFileSizeBytes();

        $handle = fopen($url, 'r', false, $context);
        if (! $handle) {
            if (config('app.dev_log')) {
                Log::warning('Failed to open URL stream', ['url' => $url]);
            }

            return null;
        }

        $data = '';
        $size = 0;

        while (! feof($handle) && $size < $maxFileSizeBytes) {
            $chunk = fread($handle, 8192);
            if ($chunk === false) {
                break;
            }

            $data .= $chunk;
            $size += strlen($chunk);
        }

        fclose($handle);

        if ($size >= $maxFileSizeBytes) {
            if (config('app.dev_log')) {
                Log::warning('File too large', ['size' => $size, 'limit' => $maxFileSizeBytes]);
            }

            return null;
        }

        return $data;
    }

    /**
     * Validate downloaded file
     */
    private function validateDownloadedFile(string $filePath, string $expectedMimeType): bool
    {
        // Check file exists and is readable
        if (! is_readable($filePath)) {
            if (config('app.dev_log')) {
                Log::warning('Downloaded file not readable', ['path' => $filePath]);
            }

            return false;
        }

        // Get actual MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $actualMimeType = $finfo->file($filePath);

        if ($actualMimeType !== $expectedMimeType) {
            if (config('app.dev_log')) {
                Log::warning('MIME type mismatch', [
                    'expected' => $expectedMimeType,
                    'actual' => $actualMimeType,
                ]);
            }

            return false;
        }

        // Additional file type specific validations
        if (str_starts_with($actualMimeType, 'image/')) {
            return $this->validateImageFile($filePath);
        } elseif (str_starts_with($actualMimeType, 'video/')) {
            return $this->validateVideoFile($filePath);
        }

        return true;
    }

    /**
     * Validate image file
     */
    private function validateImageFile(string $filePath): bool
    {
        $imageInfo = getimagesize($filePath);
        if (! $imageInfo) {
            if (config('app.dev_log')) {
                Log::warning('Invalid image file', ['path' => $filePath]);
            }

            return false;
        }

        // Check reasonable dimensions (not too large, not too small)
        [$width, $height] = $imageInfo;
        if ($width < 1 || $height < 1 || $width != 1080 || $height != 1920) {
            if (config('app.dev_log')) {
                Log::warning('Image dimensions out of range', [
                    'width' => $width,
                    'height' => $height,
                ]);
            }

            return false;
        }

        return true;
    }

    /**
     * Basic video file validation
     */
    private function validateVideoFile(string $filePath): bool
    {
        // Todo: improved video file header checks
        $size = filesize($filePath);
        $maxSize = $this->getMaxFileSizeBytes();

        return $size > 0 && $size <= $maxSize;
    }

    /**
     * Get allowed MIME types from config
     */
    private function getAllowedMimeTypes(): array
    {
        $mediaTypes = config_cache('pixelfed.media_types');

        return array_map('trim', explode(',', $mediaTypes));
    }

    /**
     * Get allowed file extensions based on MIME types from config
     */
    private function getAllowedExtensions(): array
    {
        $mimeTypes = $this->getAllowedMimeTypes();
        $extensions = [];

        $mimeToExtension = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
            'image/heic' => ['heic', 'heif'],
            'image/avif' => ['avif'],
            'video/mp4' => ['mp4'],
            'video/webm' => ['webm'],
            'video/mov' => ['mov'],
            'video/quicktime' => ['mov', 'qt'],
        ];

        foreach ($mimeTypes as $mimeType) {
            if (isset($mimeToExtension[$mimeType])) {
                $extensions = array_merge($extensions, $mimeToExtension[$mimeType]);
            }
        }

        return array_unique($extensions);
    }

    /**
     * Get max file size in bytes from config (config is in KB)
     */
    private function getMaxFileSizeBytes(): int
    {
        $maxSizeKb = config('pixelfed.max_photo_size', 15000);

        return $maxSizeKb * 1024;
    }

    /**
     * Generate cryptographically secure filename
     */
    private function generateSecureFileName(string $extension): string
    {
        $random1 = Str::random(random_int(2, 12));
        $random2 = Str::random(random_int(32, 35));
        $random3 = Str::random(random_int(1, 14));

        return $random1.'_'.$random2.'_'.$random3.'.'.$extension;
    }

    /**
     * Create story record with transaction safety
     */
    private function createStoryRecord(array $payload, $profile, array $mediaResult): void
    {
        DB::transaction(function () use ($payload, $profile, $mediaResult) {
            // Check for duplicate by object_id
            if (Story::where('object_id', $payload['id'])->exists()) {
                if (config('app.dev_log')) {
                    Log::info('Story already exists', ['object_id' => $payload['id']]);
                }

                return;
            }

            $type = $payload['attachment']['type'] === 'Image' ? 'photo' : 'video';

            $story = new Story;
            $story->profile_id = $profile->id;
            $story->object_id = $payload['id'];
            $story->size = $mediaResult['size'];
            $story->mime = data_get($payload, 'attachment.mediaType');
            $story->duration = $payload['duration'] ?? null;
            $story->media_url = data_get($payload, 'attachment.url');
            $story->type = $type;
            $story->public = false;
            $story->local = false;
            $story->active = true;
            $story->path = $mediaResult['path'];
            $story->view_count = 0;
            $story->can_reply = $payload['can_reply'] ?? false;
            $story->can_react = $payload['can_react'] ?? false;
            $story->created_at = now()->parse($payload['published']);
            $story->expires_at = now()->parse($payload['expiresAt']);
            $story->save();

            // Index the story
            $index = app(StoryIndexService::class);
            $index->indexStory($story);

            // Clear cache
            StoryService::delLatest($story->profile_id);

            if (config('app.dev_log')) {
                Log::info('Story created successfully', [
                    'story_id' => $story->id,
                    'profile_id' => $profile->id,
                ]);
            }
        });
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception)
    {
        if (config('app.dev_log')) {
            Log::error('StoryFetch job failed permanently', [
                'activity_id' => $this->activity['id'] ?? 'unknown',
                'error' => $exception->getMessage(),
                'attempts' => $this->attempts(),
            ]);
        }
    }
}
