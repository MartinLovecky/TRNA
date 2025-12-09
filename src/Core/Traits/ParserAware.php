<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Traits;

use DOMDocument;
use DOMElement;
use DOMNode;
use Yuha\Trna\Core\{Server, TmContainer};
use Yuha\Trna\Service\Aseco;

trait ParserAware
{
    protected DOMDocument $dom;

    /**
     * Initialize DOMDocument with common defaults.
     */
    protected function initDom(): void
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;
        $this->dom->preserveWhiteSpace = false;
    }

    /**
     * Parse XML string into TmContainer.
     */
    public static function fromXMLString(string $xmlContent): TmContainer
    {
        $instance = new static();

        if (method_exists($instance, 'initDom')) {
            $instance->initDom();
        }

        libxml_use_internal_errors(true);
        $instance->dom->loadXML($xmlContent, LIBXML_NOBLANKS | LIBXML_NOCDATA);
        libxml_use_internal_errors(false);

        return $instance->parseNode($instance->dom->documentElement);
    }

    /**
     * Parse XML file into TmContainer.
     */
    public static function fromXMLFile(string $filePath): TmContainer
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $xmlContent = file_get_contents($filePath);
        if ($xmlContent === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        return static::fromXMLString($xmlContent);
    }

    /**
     * Decode JSON string and convert to container.
     *
     * @param  string                    $json JSON string to decode
     * @throws \InvalidArgumentException If JSON is invalid
     */
    public static function fromJsonString(string $json): static
    {
        $data = Aseco::safeJsonDecode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return self::fromArray($data);
    }

    /**
     * Load JSON from a file and convert to container.
     *
     * @param  string            $filePath Path to JSON file
     * @throws \RuntimeException If file cannot be read or contents are invalid
     */
    public static function fromJsonFile(string $filePath): static
    {
        $json = Aseco::safeFileGetContents($filePath);

        if (!$json) {
            throw new \RuntimeException("Invalid filePath: {$filePath}");
        }

        return self::fromJsonString($json);
    }

    /**
     * Save this container to a JSON file.
     * - save location TmController/public/json/
     * @param  string $file without .json
     * @return bool   true on success
     */
    public function saveToJsonFile(string $file): bool
    {
        $json = json_encode(
            $this->jsonSerialize(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
        );

        if ($json === false) {
            return false;
        }
        $path = Server::$jsonDir . $file . 'json';
        return file_put_contents($path, $json) !== false;
    }

    /**
     * Update a value at a given dot-path inside a JSON file.
     *
     * Loads the JSON → applies update → writes back.
     *
     * @param string $filePath Path to JSON file
     * @param string $path     Dot-path inside the JSON
     * @param mixed  $value    New value
     */
    public static function updateJsonFile(string $filePath, string $path, mixed $value): bool
    {
        $container = self::fromJsonFile($filePath);
        $container->set($path, $value);

        return $container->saveToJsonFile($filePath);
    }

    /**
     * Recursively convert container and nested containers to arrays.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Return a pretty-printed JSON representation of this container.
     *
     * @return string JSON string or '{}' if encoding fails
     */
    public function __toString(): string
    {
        $json = json_encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
        return $json === false ? '{}' : $json;
    }

    /**
     * Recursively parse DOM nodes into a TmContainer.
     */
    protected function parseNode(DOMElement|DOMNode $node): mixed
    {
        $TmContainer = new TmContainer();
        $elementCount = [];
        $textContent = [];

        // handle attributes
        if ($node instanceof DOMElement && $node->hasAttributes()) {
            foreach ($node->attributes as $attribute) {
                $TmContainer->set($attribute->nodeName, $this->convertValues($attribute->nodeValue));
            }
        }

        // handle children
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $childName = $child->nodeName;
                $childContainer = $this->parseNode($child);

                if (isset($elementCount[$childName])) {
                    $elementCount[$childName]++;
                    $childName = "{$childName}_{$elementCount[$childName]}";
                } else {
                    $elementCount[$childName] = 1;
                }

                $TmContainer->set($childName, $childContainer);
            } elseif (\in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE], true)) {
                $text = trim($child->nodeValue);
                if ($text !== '') {
                    $textContent[] = $text;
                }
            }
        }

        // if only text, return primitive
        if (!empty($textContent)) {
            $combined = implode(' ', $textContent);
            if ($TmContainer->count() === 0) {
                return $this->convertValues($combined);
            }
            $TmContainer->set('#text', $combined);
        }

        return $TmContainer;
    }

    /**
     * Convert XML textual values into PHP types.
     */
    protected function convertValues(mixed $value): mixed
    {
        if (!\is_string($value)) {
            return $value;
        }

        // Try boolean
        $bool = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($bool !== null) {
            return $bool;
        }

        // Try numeric
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        // Try base64
        if (Aseco::isBase64($value)) {
            return base64_decode($value, true);
        }

        // Empty string → null
        if ($value === '') {
            return null;
        }

        // Try date
        if ($this->isDate($value)) {
            return new \DateTime($value);
        }

        return $value;
    }

    /**
     * Detects date-like strings (YYYY-MM-DD or with time).
     */
    protected function isDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2})?$/', $value) === 1;
    }
}
