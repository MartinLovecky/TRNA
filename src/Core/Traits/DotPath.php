<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Traits;

trait DotPath
{
    /**
     * Navigate to the parent TmContainer of the last segment in a dot-path.
     *
     * Supports escaped dots (\.) in keys and arrays
     * Can create missing intermediate TmContainers if $createMissing is true.
     *
     * @param  string                               $path          Dot-separated path
     * @param  bool                                 $createMissing Whether to create missing intermediate TmContainers
     * @return array{TmContainer|null, string|null} [$parentTmContainer, $lastKey]
     */
    protected function navigateToParent(string $path, bool $createMissing): array
    {
        if ($path === '') {
            return [$this, null];
        }

        $segments = preg_split('/(?<!\\\\)\./', $path);
        $segments = array_map(
            static fn ($seg) => str_replace('\.', '.', $seg),
            $segments,
        );

        $lastKey = array_pop($segments);
        if (ctype_digit((string)$lastKey)) {
            $lastKey = (int)$lastKey;
        }

        $current = $this;

        foreach ($segments as $segment) {
            if (ctype_digit((string)$segment)) {
                $segment = (int)$segment;
            }

            if (!$current->offsetExists($segment)) {
                if (!$createMissing) {
                    return [null, $lastKey];
                }

                $current[$segment] = new self();
            }

            $next = $current[$segment];

            if (!$next instanceof self) {
                return [null, $lastKey];
            }

            $current = $next;
        }

        return [$current, $lastKey];
    }
}
