<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Contracts;

use Yuha\Trna\Core\Controllers\PluginController;

/**
 * Any Plugin that need other plugin must implement setRegistry
 */
interface DependentPlugin extends PluginInterface
{
    public function setRegistry(PluginController $pluginController): void;
}
