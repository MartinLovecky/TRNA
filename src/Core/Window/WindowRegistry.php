<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\Enums\Panel;

/**
 * Registry for windows and their pages, providing encoding/decoding of actions.
 * @author Yuha <yuha@email.com>
 */
class WindowRegistry
{
    /** @var array<string,int> */
    private static $pages = [];

    /**
     * Register a window with total pages.
     *
     * @param Panel $panel      The enum identifier of the window.
     * @param int   $totalPages Total number of pages for the window.
     */
    public static function register(Panel $panel, int $totalPages): void
    {
        self::$pages[$panel->name] = max(1, $totalPages);
    }

    public static function action(Panel $panel, int $page): int
    {
        $page = self::clampPage($panel, $page);
        return ActionCodec::encodePage($panel, $page);
    }

    /**
     * Get action id for previous page
     *
     * @param  integer $currentPage
     * @return integer
     */
    public static function prev(Panel $panel, int $currentPage): int
    {
        return self::action($panel, $currentPage - 1);
    }

    /**
     * Get action id for next page
     *
     * @param  integer $currentPage
     * @return integer
     */
    public static function next(Panel $panel, int $currentPage): int
    {
        return self::action($panel, $currentPage + 1);
    }

    /**
     * Get action id for frist page
     *
     * @return integer
     */
    public static function first(Panel $panel): int
    {
        return self::action($panel, 1);
    }

    /**
     * Get action id for last page
     *
     * @return integer
     */
    public static function last(Panel $panel): int
    {
        return self::action($panel, self::$pages[$panel->name] ?? 1);
    }

    /**
     * Reserve page 0 for “close”
     *
     * @return integer
     */
    public static function close(Panel $panel): int
    {
        return ActionCodec::encodeClose($panel);
    }

    public static function choice(Panel $panel, string $choiceName): ?int
    {
        $choices = $panel->choices();
        if (!isset($choices[$choiceName])) {
            return null; // this panel doesn’t have that choice
        }
        return ActionCodec::encodeChoice($panel, $choices[$choiceName]);
    }

    private static function clampPage(Panel $panel, int $page): int
    {
        $total = self::$pages[$panel->name] ?? 1;
        return max(1, min($page, $total));
    }
}
