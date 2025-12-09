<?php

declare(strict_types=1);

namespace Yuha\Trna\Service\Internal;

use Twig\Extension\AbstractExtension;

class TwigFiltersExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return TwigFiltersProvider::getFilters();
    }
}
