<?php

namespace App\Contracts;

/**
 * Contract every captcha provider (hCaptcha, Turnstile, Cap, ...) must implement
 * so the rest of the application can stay provider-agnostic.
 */
interface CaptchaDriver
{
    /**
     * The machine name of the driver (e.g. "hcaptcha", "turnstile", "cap").
     */
    public function name(): string;

    /**
     * Whether the driver has the credentials/config it needs to operate.
     */
    public function isConfigured(): bool;

    /**
     * The name of the request field that carries the response token for this
     * provider (e.g. "h-captcha-response", "cf-turnstile-response", "cap-token").
     */
    public function responseField(): string;

    /**
     * Verify a submitted request against the provider.
     *
     * Implementations should pull the response token out of the given input
     * array using responseField().
     */
    public function verify(array $input): bool;

    /**
     * Render the widget markup to embed in a form. Optional HTML attributes
     * (e.g. ['data-theme' => 'dark']) may be passed through to the widget.
     */
    public function render(array $attributes = []): string;

    /**
     * Any <script>/<link> tags the widget needs. Returned separately so callers
     * can place them in <head> or before </body> as appropriate. May be empty
     * when render() already includes everything.
     */
    public function scripts(): string;
}
