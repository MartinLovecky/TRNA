<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Contracts;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

interface ContainerInterface extends ArrayAccess, JsonSerializable, Countable, IteratorAggregate
{
    public function set(string $path, mixed $value): static;
    public function get(string $path, mixed $default = null): mixed;
    public function has(string $path): bool;
    public function delete(string $path): static;
    public function count(): int;
    public function getIterator(): Traversable;
    public function merge(self $other): static;
    public function toArray(): array;
}
