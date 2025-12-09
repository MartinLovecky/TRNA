<?php

declare(strict_types=1);

namespace Yuha\Trna\Service\Internal;

final class YoutubeSearchResults
{
    /**
     * @param YoutubeVideoResult[] $videos
     */
    public function __construct(
        public array $videos
    ) {
    }
}
