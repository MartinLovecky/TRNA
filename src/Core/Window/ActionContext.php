<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\Enums\{ActionKind, Panel};

final class ActionContext
{
    public function __construct(
        public readonly Panel $panel,
        public readonly ActionKind $kind,
        public readonly int $val
    ) {
    }

    public function toArray(): array
    {
        return [
            'panel' => $this->panel->name,
            'panel_value' => $this->panel->value,
            'kind'  => $this->kind->name,
            'kind_value' => $this->kind->value,
            'value' => $this->val,
        ];
    }
}
