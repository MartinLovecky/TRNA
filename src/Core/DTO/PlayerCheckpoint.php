<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\DTO;

final class PlayerCheckpoint
{
    public function __construct(
        public int $bestFin = PHP_INT_MAX,
        public int $currFin = PHP_INT_MAX,
        public array $bestCps = [],
        public array $currCps = [],
        public int $dedirec = 0,
        public ?int $loclrec = null
    ) {
    }
}
