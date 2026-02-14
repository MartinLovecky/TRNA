<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\DTO;

final class YoutubeVideoResult
{
    public function __construct(
        public string $videoLink,
        public string $title,
        public string $channel,
        public string $description,
        public ?\DateTimeImmutable $publishedAt,
        public string $thumbnail,
    ) {
    }
}
