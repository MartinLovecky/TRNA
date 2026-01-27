<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\Enums\Window;

/**
 * Registry for windows and their pages, providing encoding/decoding of actions.
 * @author Yuha <yuha@email.com>
 */
class Registry
{
    /** window => total pages */
    private array $totalPages = [];

    /** playerId => [window => current page] */
    private array $currentPages = [];

    /** playerId => last interaction timestamp */
    private array $lastTouch = [];

    /**
     * Register a window with total pages.
     *
     * @param Window $window The enum identifier of the window.
     * @param int    $pages  Total number of pages for the window.
     */
    public function register(Window $window, int $pages): void
    {
        $this->totalPages[$window->name] = max(1, $pages);
    }

    public function setPage(string $playerId, Window $window, int $page): void
    {
        $this->touch($playerId);

        $page = max(1, min($page, $this->total($window)));
        $this->currentPages[$playerId][$window->value] = $page;
    }

    public function current(?string $playerId, Window $window): int
    {
        if (!$playerId) {
            return 1; // non-player view always page 1
        }

        $this->touch($playerId);

        return $this->currentPages[$playerId][$window->value] ?? 1;
    }

    /**
     * Get action id for previous page
     *
     */
    public function prev(string $playerId, Window $window): void
    {
        $this->setPage($playerId, $window, $this->current($playerId, $window) - 1);
    }

    /**
     * set id for next page
     *
     */
    public function next(string $playerId, Window $window): void
    {
        $this->setPage($playerId, $window, $this->current($playerId, $window) + 1);
    }

    /**
     * set action id for frist page
     *
     */
    public function first(string $playerId, Window $window): void
    {
        $this->setPage($playerId, $window, 1);
    }

    /**
     * set action id for last page
     *
     */
    public function last(string $playerId, Window $window): void
    {
        $this->setPage($playerId, $window, $this->total($window));
    }

    public function total(Window $window): int
    {
        return $this->totalPages[$window->value] ?? 1;
    }

    public function cleanup(int $ttl = 1800): void
    {
        $now = time();

        foreach ($this->lastTouch as $playerId => $last) {
            if ($now - $last > $ttl) {
                unset($this->lastTouch[$playerId], $this->currentPages[$playerId]);
            }
        }
    }

    private function touch(string $playerId): void
    {
        $this->lastTouch[$playerId] = time();
    }
}
