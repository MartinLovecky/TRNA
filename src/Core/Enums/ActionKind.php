<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum ActionKind: int
{
    case Page   = 1;  // Regular pages
    case Choice = 2;  // Yes/No/Cancel/Pass
    case Chat   = 3;  // Chat manialink commands (not implemented)
    case Close  = 4;  // Close action
}
