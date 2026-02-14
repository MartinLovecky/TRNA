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

    /** login => [window => current page] */
    private array $currentPages = [];

    /** login => last interaction timestamp */
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

    public function setPage(string $login, Window $window, int $page): void
    {
        $this->touch($login);

        $page = max(1, min($page, $this->total($window)));
        $this->currentPages[$login][$window->value] = $page;
    }

    public function current(?string $login, Window $window): int
    {
        if (!$login) {
            return 1; // non-player view always page 1
        }

        $this->touch($login);

        return $this->currentPages[$login][$window->value] ?? 1;
    }

    /**
     * Get action id for previous page
     *
     */
    public function prev(string $login, Window $window): void
    {
        $this->setPage($login, $window, $this->current($login, $window) - 1);
    }

    /**
     * set id for next page
     *
     */
    public function next(string $login, Window $window): void
    {
        $this->setPage($login, $window, $this->current($login, $window) + 1);
    }

    /**
     * set action id for frist page
     *
     */
    public function first(string $login, Window $window): void
    {
        $this->setPage($login, $window, 1);
    }

    /**
     * set action id for last page
     *
     */
    public function last(string $login, Window $window): void
    {
        $this->setPage($login, $window, $this->total($window));
    }

    public function total(Window $window): int
    {
        return $this->totalPages[$window->value] ?? 1;
    }

    public function cleanup(int $ttl = 1800): void
    {
        $now = time();

        foreach ($this->lastTouch as $login => $last) {
            if ($now - $last > $ttl) {
                unset($this->lastTouch[$login], $this->currentPages[$login]);
            }
        }
    }

    private function touch(string $login): void
    {
        $this->lastTouch[$login] = time();
    }
}
