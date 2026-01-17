<?php

declare(strict_types=1);

namespace Yuha\Trna\Core;

/**
 * @property-read self $aqua
 * @property-read self $white
 * @property-read self $yellow
 * @property-read self $red
 * @property-read self $gray
 * @property-read self $darkGray
 * @property-read self $black
 * @property-read self $green
 * @property-read self $orange
 * @property-read self $brown
 * @property-read self $blue
 * @property-read self $lightYellow
 * @property-read self $pink
 * @property-read self $magenta
 * @property-read self $darkBlue
 * @property-read self $lightGreen
 * @property-read self $lightBlue
 * @property-read self $teal
 * @property-read self $gold
 * @property-read self $silver
 * @property-read self $bronze
 * @property-read self $z
 * @property-read self $b
 * @property-read self $i
 */
class Color
{
    private array $map = [
        'aqua'        => '$0ff',
        'white'       => '$fff',
        'yellow'      => '$ff0',
        'red'         => '$f00',
        'gray'        => '$888',
        'darkGray'    => '$bbb',
        'black'       => '$000',
        'green'       => '$0f0',
        'orange'      => '$fa0',
        'brown'       => '$d80',
        'blue'        => '$00f',
        'lightYellow' => '$ff3',
        'pink'        => '$f8f',
        'magenta'     => '$f0f',
        'darkBlue'    => '$006',
        'lightGreen'  => '$0c0',
        'lightBlue'   => '$28b',
        'teal'        => '$0b3',
        'gold'        => '$fd0',
        'silver'      => '$ccc',
        'bronze'      => '$b52',
    ];

    private static array $styleMap = [
        'i' => '$i', // Italic
        'b' => '$o', // Bold
        's' => '$s', // Shadow
        'w' => '$w', // Wide
        'n' => '$n', // Narrow
        'l' => '$l', // Link
        't' => '$t', // Uppercase
        'm' => '$m', // Reset text widht
        'g' => '$g', // Reset color
        'z' => '$z',  // Reset all styling to default.
    ];

    public function __construct(public string $code = '')
    {
    }

    public function __toString(): string
    {
        return $this->code;
    }

    /** Add style */
    private function addStyle(string $style): self
    {
        return new self($this->code . $style);
    }

    /** Apply multiple styles via shortcut string */
    public function styles(string $shortcuts): self
    {
        $newCode = $this->code;
        foreach (str_split($shortcuts) as $key) {
            if (isset(self::$styleMap[$key])) {
                $newCode .= self::$styleMap[$key];
            }
        }
        return new self($newCode);
    }

    /** Magic getter: either return a color or apply style shortcuts */
    public function __get(string $name): ?self
    {
        // If the name is a color, append the color code to the current code
        if (isset($this->map[$name])) {
            return new self($this->code . $this->map[$name]);
        }

        // If the name is a style shortcut (single or combined letters)
        $letters = str_split($name);
        $newCode = $this->code;
        $applied = false;
        foreach ($letters as $letter) {
            if (isset(self::$styleMap[$letter])) {
                $newCode .= self::$styleMap[$letter];
                $applied = true;
            }
        }
        if ($applied) {
            return new self($newCode);
        }

        return null;
    }

    // explicit individual style methods (for chaining if preferred)
    public function i(): self
    {
        return $this->addStyle('$i');
    }
    public function b(): self
    {
        return $this->addStyle('$o');
    }
    public function s(): self
    {
        return $this->addStyle('$s');
    }
    public function w(): self
    {
        return $this->addStyle('$w');
    }
    public function n(): self
    {
        return $this->addStyle('$n');
    }
    public function g(): self
    {
        return $this->addStyle('$g');
    }
    public function z(): self
    {
        return $this->addStyle('$z');
    }

    // Links
    public function h(string $id): self
    {
        return $this->addStyle('$h' . $id);
    }
    public function l(string $url): self
    {
        return $this->addStyle('$l' . $url . '$l');
    }

    public function escape(): self
    {
        return $this->addStyle('$$');
    }
}
