<?php

declare(strict_types=1);

namespace Yuha\Trna\Service\Internal;

use Twig\TwigFilter;

class TwigFiltersProvider
{
    public static function getFilters(): array
    {
        return [
            // Sum an array of numbers
            new TwigFilter('sum', static fn (array $array): float => array_sum($array)),

            // Format seconds as MM:SS
            new TwigFilter(
                'format_time',
                static fn (int|float $seconds): string => \sprintf('%02d:%02d', (int)floor($seconds / 60), (int)$seconds % 60),
            ),

            // Enumerate array items with index and value (functional style)
            new TwigFilter('enumerate', static fn (array $array, int $start = 0): array => array_map(
                static fn ($value, $index) => ['index' => $index + $start, 'value' => $value],
                $array,
                array_keys($array),
            )),

            // Slice array with optional end index (functional style)
            new TwigFilter(
                'slice_range',
                static fn (array $array, int $start, ?int $end = null): array => $end === null ? \array_slice($array, $start) : \array_slice($array, $start, $end - $start),
            ),

            // Escape XML safely
            new TwigFilter('xml_escape', static fn ($string): string => htmlspecialchars((string)$string, ENT_QUOTES | ENT_XML1, 'UTF-8')),

            // Safe array access with default
            new TwigFilter('safe_access', static fn (array $array, $key, $default = null) => $array[$key] ?? $default),

            // Truncate string with optional word preservation
            new TwigFilter('truncate', static function (string $string, int $length = 30, bool $preserve = false, string $separator = '...'): string {
                if (mb_strlen($string, 'UTF-8') <= $length) {
                    return $string;
                }

                if ($preserve) {
                    $break = mb_strrpos(mb_substr($string, 0, $length, 'UTF-8'), ' ');
                    if ($break !== false) {
                        $length = $break;
                    }
                }

                return rtrim(mb_substr($string, 0, $length, 'UTF-8')) . $separator;
            }),

            // Remove UCS-4 (4-byte UTF-8) characters like emojis
            new TwigFilter('strip_ucs4', static fn (string $text): string => preg_replace('/[\xF0-\xF7][\x80-\xBF]{3}/', '', $text)),

            // Flatten nested arrays (functional, recursive)
            new TwigFilter('flatten', static fn (array $array): array => iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array)))),
        ];
    }
}
