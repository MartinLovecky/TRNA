<?php

declare(strict_types=1);

namespace Yuha\Trna\Infrastructure\Tmx;

final readonly class TmxRecord
{
    public function __construct(
        public int $replayid,
        public int $userid,
        public string $name,
        public int $time,
        public ?\DateTimeImmutable $replayat,
        public ?\DateTimeImmutable $trackat,
        public bool $approved,
        public mixed $score,
        public mixed $expires,
        public mixed $lockspan,
    ) {
    }

    public function getReplayUrl(): string
    {
        return "https://tmnforever.tm-exchange.com/get.aspx?action=recordgbx&id={$this->replayid}";
    }
}
