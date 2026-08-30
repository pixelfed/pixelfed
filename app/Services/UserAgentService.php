<?php

namespace App\Services;

use DeviceDetector\DeviceDetector;

class UserAgentService
{
    protected DeviceDetector $dd;

    public function __construct(?string $userAgent = null)
    {
        $this->dd = new DeviceDetector($userAgent ?? '');
        $this->dd->parse();
    }

    /**
     * Set the user agent string and re-parse.
     */
    public function setUserAgent(string $userAgent): static
    {
        $this->dd->setUserAgent($userAgent);
        $this->dd->parse();

        return $this;
    }

    /**
     * Check if the device is a mobile phone or phablet.
     */
    public function isMobile(): bool
    {
        return ! $this->dd->isDesktop();
    }

    /**
     * Get the device brand and model (e.g. "Apple iPhone", "Samsung Galaxy S21").
     * Falls back to the device type name if brand/model aren't available.
     */
    public function device(): string
    {
        $brand = $this->dd->getBrandName();
        $model = $this->dd->getModel();

        if ($brand && $model) {
            return trim("{$brand} {$model}");
        }

        if ($model) {
            return $model;
        }

        if ($brand) {
            return $brand;
        }

        $deviceName = $this->dd->getDeviceName();

        return $deviceName ?: 'Unknown';
    }

    /**
     * Get the browser name (e.g. "Chrome", "Firefox", "Safari").
     */
    public function browser(): string
    {
        $client = $this->dd->getClient();

        if (is_array($client) && ! empty($client['name'])) {
            return $client['name'];
        }

        return 'Unknown';
    }

    /**
     * Get the operating system / platform name (e.g. "Windows", "iOS", "Android").
     */
    public function platform(): string
    {
        $os = $this->dd->getOs();

        if (is_array($os) && ! empty($os['name'])) {
            return $os['name'];
        }

        return 'Unknown';
    }
}
