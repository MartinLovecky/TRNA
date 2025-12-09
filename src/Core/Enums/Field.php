<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum Field: int
{
    case id        = 0;
    case name      = 1;
    case userid    = 2;
    case author    = 3;
    case uploaded  = 4;
    case updated   = 5;
    case visible   = 6;
    case type      = 7;
    case envir     = 8;
    case mood      = 9;
    case style     = 10;
    case routes    = 11;
    case length    = 12;
    case diffic    = 13;
    case lbrating  = 14;
    case game      = 15;
    case comment   = 16;
}
