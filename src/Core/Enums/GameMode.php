<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum GameMode: int
{
    case Rounds = 0;
    case Race   = 1;
    case Team   = 2;
    case Laps   = 3;
    case Stunts = 4;
    case Cup    = 5;
    case Score  = 7;
}
