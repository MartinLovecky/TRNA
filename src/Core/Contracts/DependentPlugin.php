<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Contracts;

use Yuha\Trna\Core\Controllers\PluginController;

interface DependentPlugin extends PluginInterface
{
    public function setRegistry(PluginController $pluginController): void;
}
