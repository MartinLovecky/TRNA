<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Enums;

enum ActionResult
{
    case Closed;
    case Handled;
    case NotHandled;
    case NoAction;
}
