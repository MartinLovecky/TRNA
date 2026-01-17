<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\Enums\Panel;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;

class Window
{
    use LoggerAware;
    private const ROWS_PER_PAGE = 8;

    public function __construct(private WindowData $windowData)
    {
        $this->initLog('WindowBuilder');
    }

    /**
     * Build the window data for ManiaLink.
     */
    public function build(Panel $panel, TmContainer $player, string $header = 'help'): array
    {
        $rows = $this->windowData->getData($panel, $player);
        $totalPages = $this->calculateTotalPages($rows);

        WindowRegistry::register($panel, $totalPages);

        $currentPage = $player->get("{$panel->name}.currentPage", 1);
        $pageRows = $this->getPageRows($rows, $currentPage);

        return [
            'id'          => $panel->value,
            'header'      => "{$panel->name}{$header}",
            'data'        => $pageRows,
            'current'     => $currentPage,
            'total'       => $totalPages,
            'first'       => WindowRegistry::first($panel),
            'last'        => WindowRegistry::last($panel),
            'prev'        => WindowRegistry::prev($panel, $currentPage),
            'next'        => WindowRegistry::next($panel, $currentPage),
            'close'       => WindowRegistry::close($panel),
            'yes'         => WindowRegistry::choice($panel, 'yes'),
            'no'          => WindowRegistry::choice($panel, 'no'),
            'cancel'      => WindowRegistry::choice($panel, 'cancel'),
            'pass'        => WindowRegistry::choice($panel, 'pass'),
        ];
    }

    /**
     * Get the slice of rows for the current page.
     */
    private function getPageRows(array $rows, int $page): array
    {
        $offset = ($page - 1) * self::ROWS_PER_PAGE;
        return \array_slice($rows, $offset, self::ROWS_PER_PAGE);
    }

    /**
     * Calculate total pages for given rows.
     */
    private function calculateTotalPages(array $rows): int
    {
        return (int) ceil(\count($rows) / self::ROWS_PER_PAGE);
    }
}
