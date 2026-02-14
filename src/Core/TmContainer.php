<?php

declare(strict_types=1);

namespace Yuha\Trna\Core;

use ArrayIterator;
use ArrayObject;
use Yuha\Trna\Core\Contracts\ContainerInterface;
use Yuha\Trna\Core\Traits\{ArrayForwarder, DotPath, ParserAware};

/**
 * Recursive container supporting dot-path access
 *
 * Implements ArrayAccess<string, mixed> for direct property-style access.
 *
 * @implements \ArrayAccess<string, mixed>
 */
class TmContainer extends ArrayObject implements ContainerInterface
{
    use ArrayForwarder;
    use DotPath;
    use ParserAware;

    public function __construct()
    {
        parent::__construct([], ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Set a value at the dotted path. Creates intermediate nodes if missing.
     *
     * @return $this
     */
    public function set(string $path, mixed $value): static
    {
        [$parent, $lastKey] = $this->navigateToParent($path, true);

        if ($parent === null) {
            return $this;
        }

        if (ctype_digit((string)$lastKey)) {
            $lastKey = (int)$lastKey;
        }

        if ($parent instanceof self || \is_array($parent)) {
            $parent[$lastKey] = $value;
        }

        return $this;
    }

    /**
     * Set multiple key => value pairs at once.
     *
     * Supports dot-path keys and method chaining.
     *
     * @param array<string, mixed> $values
     */
    public function setMultiple(array $values): static
    {
        foreach ($values as $path => $value) {
            $this->set($path, $value);
        }
        return $this;
    }

    /**
     * Get a value at a dot-path, with default.
     *
     */
    public function get(string $path, mixed $default = null): mixed
    {
        [$parent, $lastKey] = $this->navigateToParent($path, false);

        if ($parent === null) {
            return $default;
        }

        if (ctype_digit((string)$lastKey)) {
            $lastKey = (int)$lastKey;
        }

        return match (true) {
            $parent instanceof self => $parent->offsetExists($lastKey) ? $parent[$lastKey] : $default,
            \is_array($parent) => \array_key_exists($lastKey, $parent) ? $parent[$lastKey] : $default,
            default => $default,
        };
    }

    /**
     * Check if a dotted path exists.
     *
     */
    public function has(string $path): bool
    {
        [$parent, $lastKey] = $this->navigateToParent($path, false);

        if ($parent === null) {
            return false;
        }

        if (ctype_digit((string)$lastKey)) {
            $lastKey = (int)$lastKey;
        }

        return $parent->offsetExists($lastKey);
    }

    /**
     * Check if a collection at the given dot-path contains a value.
     *
     * - Use sparingly — this is O(n).
     */
    public function in(string $path, mixed $needle, bool $strict = true): bool
    {
        $target = $this->get($path);

        if (!$target instanceof self) {
            return false;
        }

        foreach ($target as $value) {
            if ($strict ? $value === $needle : $value === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Delete a value at a dot-path.
     *
     */
    public function delete(string $path): static
    {
        [$parent, $lastKey] = $this->navigateToParent($path, false);

        if ($parent === null) {
            return $this;
        }

        if (ctype_digit((string)$lastKey)) {
            $lastKey = (int)$lastKey;
        }

        if ($parent instanceof self || \is_array($parent)) {
            unset($parent[$lastKey]);
        }

        return $this;
    }

    /**
     * Recursively execute a callback on this container and all nested containers.
     *
     * Intended for side effects only; callback return values are ignored.
     *
     * @param  callable(TmContainer): void $callback
     * @return $this
     */
    public function each(callable $callback): static
    {
        $callback($this);

        foreach ($this as $value) {
            if ($value instanceof self) {
                $value->each($callback);
            }
        }

        return $this;
    }

    /**
     * Apply a callback to each direct child and return the results as an array.
     *
     * Does not modify the container.
     *
     * @template T
     * @param  callable(mixed): T $callback
     * @return array<int, T>
     */
    public function map(callable $callback): array
    {
        $result = [];
        foreach ($this as $value) {
            $result[] = $callback($value);
        }
        return $result;
    }

    /**
     *
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * easy access to count
     *
     * @return integer
     */
    public function count(): int
    {
        return parent::count();
    }

    /**
     *
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this);
    }

    /**
     *
     */
    public function first(): mixed
    {
        foreach ($this as $value) {
            return $value;
        }
        return null;
    }

    /**
     * Merge another container into this one.
     *
     * Conflicting scalar values are collected into an array under the key "<key>_merged".
     *
     * @throws \InvalidArgumentException If $other is not a Container instance
     */
    public function merge(ContainerInterface $other): static
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only merge instances of Container');
        }

        foreach ($other as $key => $value) {
            if ($value instanceof self && $value->count() === 0) {
                continue;
            }

            if (!$this->offsetExists($key)) {
                $this[$key] = $value;
                continue;
            }

            $existing = $this[$key];

            if ($existing instanceof self && $value instanceof self) {
                $existing->merge($value);
            } else {
                $mergedKey = $key . '_merged';
                if (!$this->offsetExists($mergedKey)) {
                    $this[$mergedKey] = [];
                }
                $mergedArray = &$this[$mergedKey];
                if (!\in_array($existing, $mergedArray, true)) {
                    $mergedArray[] = $existing;
                }
                $mergedArray[] = $value;

                unset($this[$key]);
            }
        }

        return $this;
    }

    /**
     * Create a container recursively from an array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data = []): static
    {
        $container = new static();

        foreach ($data as $k => $v) {
            if (ctype_digit((string)$k)) {
                $k = (int) $k;
            }

            $container[$k] = \is_array($v)
                ? static::fromArray($v)
                : $v;
        }

        return $container;
    }

    /**
     * Recursively convert container to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $arr = [];
        foreach ($this as $k => $v) {
            $arr[$k] = ($v instanceof self) ? $v->toArray() : $v;
        }
        return $arr;
    }

    /**
     * overwrite offsetSet
     *
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        if (\is_array($value)) {
            $value = self::fromArray($value);
        }

        parent::offsetSet($key, $value);
    }

    /**
     * overwrite offsetUnset
     *
     */
    public function offsetUnset(mixed $key): void
    {
        parent::offsetUnset($key);
    }
}
