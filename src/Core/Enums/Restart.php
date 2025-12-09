<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum Restart
{
    case NONE;
    case MAP;
    case MODE;
    case SERVER;
}
