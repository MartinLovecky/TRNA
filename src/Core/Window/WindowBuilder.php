<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\Enums\Panel;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;

class WindowBuilder
{
    use LoggerAware;
    private const ROWS_PER_PAGE = 8;

    public function __construct(private WindowData $windowData)
    {
        $this->initLog('WindowBuilder');
    }

    public function data(Panel $panel, TmContainer $player): array
    {
        $data = $this->windowData->getData($panel, $player);
        WindowRegistry::register($panel, $this->totalPages($data));

        return $this->window($panel, $player, $data);
    }

    private function window(Panel $panel, TmContainer $player, array $data): array
    {
        if (!WindowRegistry::has($panel)) {
            $this->logDebug("Window Registry doesn't have $panel->name");
            return []; // NON EXISTEND PANEL should not happen
        }

        $totalPages = WindowRegistry::getTotalPages($panel);

        if ($totalPages === 0) {
            $this->logDebug("We got total pages: {$totalPages}");
            return []; // should not happen
        }

        $currentPage = $player->get("{$panel->name}.currentPage", 1);
        $currentPage = WindowRegistry::clampPage($panel, $currentPage);

        $rowsPerPage = self::ROWS_PER_PAGE;
        $offset = ($currentPage - 1) * $rowsPerPage;

        $pageData = \array_slice($data, $offset, $rowsPerPage);

        return [
            'data'       => $pageData,
            'header'     => $panel->name,
            'totalPages' => $totalPages,
            'firstPage'  => WindowRegistry::firstAction($panel),
            'lastPage'   => WindowRegistry::lastAction($panel),
            'prevPage'   => WindowRegistry::prevAction($panel, $currentPage),
            'nextPage'   => WindowRegistry::nextAction($panel, $currentPage),
        ];
    }

    private function totalPages(array $rows): int
    {
        return (int) ceil(\count($rows) / self::ROWS_PER_PAGE);
    }
}
