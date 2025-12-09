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
    /** @var int Multiplier for encoding pages into action IDs. */
    private const PAGE_MULTIPLIER = 100;
    /** @var array<string, array{name: string, totalPages: int}> Registered windows. */
    private static $storage = [];

    /**
     * Register a window with total pages.
     *
     * @param  Panel $panel      The enum identifier of the window.
     * @param  int   $totalPages Total number of pages for the window.
     * @return void
     */
    public static function register(Panel $panel, int $totalPages)
    {
        self::$storage[$panel->value] = [
            'name' => $panel->name,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Get the name of a registered window.
     *
     * @return string|null Returns the window name or null if not registered.
     */
    public static function getName(Panel $panel): ?string
    {
        return self::$storage[$panel->value]['name'] ?? null;
    }

    /**
     * Check if a window is registered.
     *
     * @return bool True if the window is registered, false otherwise.
     */
    public static function has(Panel $panel): bool
    {
        return isset(self::$storage[$panel->value]);
    }

    /**
     * Get total pages of a window.
     *
     * @return int Number of pages, or 0 if the window is not registered.
     */
    public static function getTotalPages(Panel $panel): int
    {
        return self::$storage[$panel->value]['totalPages'] ?? 0;
    }

    /**
     * Encode a window and page into an action ID.
     *
     * @return int Encoded action id.
     */
    public static function encode(Panel $panel, int $page): int
    {
        return $panel->value * self::PAGE_MULTIPLIER + $page;
    }

    /**
     * Decode an action ID into Panel and page.
     *
     * @param int $action Encoded action id.
     *
     * @throws \InvalidArgumentException If the window id is unknown.
     * @return array{0: Panel, 1: int}   Array containing Panel and page number.
     */
    public static function decode(int $action): array
    {
        $panel = intdiv($action, self::PAGE_MULTIPLIER);
        $page  = $action % self::PAGE_MULTIPLIER;
        $enum  = Panel::tryFrom($panel);

        if ($enum === null) {
            throw new \InvalidArgumentException("Unknown window: {$panel} for action {$action}");
        }

        return [$enum, $page];
    }

    /**
     * Clamp a page number to valid range.
     *
     * @return int Validated page number (minimum 1, maximum total pages).
     */
    public static function clampPage(Panel $panel, int $page): int
    {
        $total = self::getTotalPages($panel);
        if ($total === 0) {
            return 1;
        }
        return max(1, min($page, $total));
    }

    /**
     * Jump to any action id min max
     *
     * @param  integer $page
     * @return integer
     */
    public static function jumpToAction(Panel $panel, int $page): int
    {
        $page = self::clampPage($panel, $page);
        return self::encode($panel, $page);
    }

    /**
     * Get action id for previous page
     *
     * @param  integer $currentPage
     * @return integer
     */
    public static function prevAction(Panel $panel, int $currentPage): int
    {
        $target = self::clampPage($panel, $currentPage - 1);
        return self::encode($panel, $target);
    }

    /**
     * Get action id for next page
     *
     * @param  integer $currentPage
     * @return integer
     */
    public static function nextAction(Panel $panel, int $currentPage): int
    {
        $target = self::clampPage($panel, $currentPage + 1);
        return self::encode($panel, $target);
    }

    /**
     * Get action id for frist page
     *
     * @return integer
     */
    public static function firstAction(Panel $panel): int
    {
        return self::encode($panel, 1);
    }

    /**
     * Get action id for last page
     *
     * @return integer
     */
    public static function lastAction(Panel $panel): int
    {
        $last = self::getTotalPages($panel);
        return self::encode($panel, $last);
    }
}
