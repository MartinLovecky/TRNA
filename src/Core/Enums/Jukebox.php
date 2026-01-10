<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum Jukebox
{
    public static function action(string $in): bool
    {
        return \in_array($in, ['juke', 'jukebox', 'list'], true);
    }
}
