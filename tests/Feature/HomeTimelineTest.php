<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression tests for two related bugs that together caused HTTP 500 on the
 * home timeline endpoint for Mastodon-compatible clients (including the
 * Pixelfed Android app).
 *
 * Bug 1 — Issue #6609: Handler.php returns HTTP 500 for ValidationException
 * Bug 2 — Issue #6610: timelineHome rejects empty max_id= / min_id= parameters
 */
class HomeTimelineTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): User
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['read']);

        return $user;
    }

    // -------------------------------------------------------------------------
    // Bug #6609 — Exception handler returns 500 instead of 422
    // -------------------------------------------------------------------------

    /**
     * ValidationException must produce HTTP 422, not 500.
     *
     * Root cause: Handler.php called method_exists($exception, 'getStatusCode')
     * which is false for Illuminate\Validation\ValidationException (the class
     * exposes $status = 422 but has no getStatusCode() method), so the fallback
     * of 500 was used for every JSON API validation failure.
     */
    #[Test]
    public function json_api_validation_failure_returns_422_not_500(): void
    {
        $this->authenticatedUser();

        // max_id=not-a-number is non-null and not an integer.
        // 'nullable|integer' fails on it → ValidationException → must be 422.
        $response = $this->getJson('/api/v1/timelines/home?max_id=not-a-number');

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
        $response->assertJsonPath('errors.max_id.0', fn ($msg) => str_contains($msg, 'integer'));
    }

    // -------------------------------------------------------------------------
    // Bug #6610 — timelineHome rejects empty max_id= / min_id=
    // -------------------------------------------------------------------------

    /**
     * An empty max_id= query parameter must not cause a server error.
     *
     * Root cause: timelineHome used 'sometimes|integer' for max_id/min_id while
     * every other timeline method in ApiV1Controller used 'nullable|integer'.
     * The ConvertEmptyStringsToNull middleware converts max_id= to null before
     * validation. With 'sometimes', the key is still "present" (as null) and the
     * integer rule runs on null → ValidationException → triggered bug #6609 → 500.
     * With 'nullable', a null value skips the integer check → no exception.
     *
     * The Pixelfed Android app (okhttp/4.12.0) sends max_id= on every first
     * home timeline load, making the app unusable without this fix.
     */
    #[Test]
    public function timeline_home_accepts_empty_max_id_parameter(): void
    {
        $this->authenticatedUser();

        // Mirrors the exact request sent by the Pixelfed Android app on first load.
        $response = $this->getJson('/api/v1/timelines/home?limit=20&max_id=&_pe=1&limit=20');

        $response->assertSuccessful();
    }

    #[Test]
    public function timeline_home_accepts_empty_min_id_parameter(): void
    {
        $this->authenticatedUser();

        $response = $this->getJson('/api/v1/timelines/home?limit=20&min_id=');

        $response->assertSuccessful();
    }

    #[Test]
    public function timeline_home_still_validates_non_null_non_integer_max_id(): void
    {
        // Ensure the nullable change does not make the endpoint accept garbage input.
        // A non-empty, non-integer max_id must still fail validation (422, not 200).
        $this->authenticatedUser();

        $response = $this->getJson('/api/v1/timelines/home?max_id=abc');

        $response->assertStatus(422);
    }
}
