<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum Rec: int // TMX
{
    case replayid = 0;
    case userid   = 1;
    case name     = 2;
    case time     = 3;
    case replayat = 4;
    case trackat  = 5;
    case approved = 6;
    case score    = 7;
    case expires  = 8;
    case lockspan = 9;
}
