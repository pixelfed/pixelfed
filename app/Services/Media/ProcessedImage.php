<?php

namespace App\Services\Media;

final readonly class ProcessedImage
{
    public function __construct(
        public string $thumbnail,
        public string $extension,
        public int $width,
        public int $height,
        public int $originalWidth,
        public int $originalHeight,
        public string $orientation,
        public ?string $main = null,
        public ?string $blurhash = null,
    ) {}

    public function mime(): string
    {
        return 'image/'.($this->extension === 'jpg' ? 'jpeg' : $this->extension);
    }

    public function resized(): bool
    {
        return $this->width !== $this->originalWidth || $this->height !== $this->originalHeight;
    }
}
