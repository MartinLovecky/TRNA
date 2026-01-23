<?php

declare(strict_types=1);

namespace Yuha\Trna\Infrastructure\Xml;

use DOMDocument;
use DOMElement;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Service\Aseco;
use Yuha\Trna\Service\Internal\Arr;

final class RpcConverter
{
    /**
     * Serialize a PHP values to an appropriate XML-RPC type element.
     *
     * @param  mixed       $value PHP value to map
     * @param  DOMDocument $dom   DOMDocument to create elements
     * @return DOMElement  corresponding XML-RPC type element
     */
    public static function serialize(mixed $value, DOMDocument $dom): DOMElement
    {
        if ($value instanceof \DateTimeInterface) {
            return $dom->createElement('dateTime.iso8601', $value->format('Ymd\TH:i:s'));
        }

        if (\is_resource($value) && get_resource_type($value) === 'stream') {
            $data = stream_get_contents($value);
            return $dom->createElement('base64', base64_encode($data));
        }

        if (\is_string($value)) {
            if (Aseco::isBase64($value)) {
                return $dom->createElement(
                    'base64',
                    $value === '' ? 'AA==' : $value,
                );
            }
            if (!mb_check_encoding($value, 'UTF-8')) {
                return $dom->createElement('base64', base64_encode($value));
            }
        }

        $type = \gettype($value);

        if ($type === 'array' && Arr::isAssoc($value)) {
            return self::structToElement((object)$value, $dom);
        }

        return match ($type) {
            'string'  => self::createStringElement($value, $dom),
            'boolean' => $dom->createElement('boolean', $value ? '1' : '0'),
            'integer' => $dom->createElement('int', htmlspecialchars((string)$value, ENT_XML1, 'UTF-8')),
            'double'  => $dom->createElement('double', (string)$value),
            'array'   => self::arrayToElement($value, $dom),
            'object'  => self::structToElement($value, $dom),
            default   => $dom->createElement('nil'),
        };
    }

    /**
     * Deserialize an XML-RPC values to an appropriate PHP values.
     *
     * @param  DOMElement $element XML-RPC type
     * @throws \Exception If element type is unknown
     * @return mixed      corresponding PHP value
     */
    public static function deserialize(DOMElement $element): mixed
    {
        $tag = $element->tagName;
        if ($tag === 'value') {
            foreach ($element->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    return self::deserialize($child);
                }
            }
            return null;
        }

        return match ($tag) {
            'string' => trim($element->textContent),
            'int', 'i4' => filter_var($element->textContent, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
            'boolean' => filter_var($element->textContent, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
            'double' => filter_var($element->textContent, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
            'dateTime.iso8601' => self::deserializeDateTime($element->textContent),
            'base64' => self::safeBase64Decode($element->textContent),
            'array' => self::deserializeArray($element),
            'struct' => self::deserializeStruct($element),
            'fault' => self::deserializeStruct($element),
            'nil' => null,
            default => throw new \Exception("Unknown element type '{$element->tagName}' in value processing."),
        };
    }

    /**
     * Creates a <string> element, using CDATA if necessary.
     *
     */
    private static function createStringElement(string $value, DOMDocument $dom): DOMElement
    {
        $element = $dom->createElement('string');
        if (str_contains($value, '<manialink>')) {
            $cdata = $dom->createCDATASection($value);
            $element->appendChild($cdata);
        } else {
            $element->appendChild($dom->createTextNode($value));
        }
        return $element;
    }

    /**
     * Converts a PHP array to an XML-RPC <array> element.
     *
     * @param  array       $array PHP array to convert
     * @param  DOMDocument $dom   DOMDocument to create elements
     * @return DOMElement  corresponding XML-RPC <array> element
     */
    private static function arrayToElement(array $array, DOMDocument $dom): DOMElement
    {
        $arrayElement = $dom->createElement('array');
        $dataElement = $dom->createElement('data');
        foreach ($array as $item) {
            $valueElement = $dom->createElement('value');
            $typeElement = self::serialize($item, $dom);
            $valueElement->appendChild($typeElement);
            $dataElement->appendChild($valueElement);
        }
        // prevent empty <data/> element
        if ($dataElement->childNodes->length === 0) {
            $arrayElement->appendChild($dom->createTextNode(''));
        }

        $arrayElement->appendChild($dataElement);
        return $arrayElement;
    }

    /**
     * Converts PHP object to an XML-RPC <struct> element.
     *
     * @param  object      $object PHP object to convert
     * @param  DOMDocument $dom    DOMDocument to create elements
     * @return DOMElement  corresponding XML-RPC <struct> element
     */
    private static function structToElement(object $object, DOMDocument $dom): DOMElement
    {
        $structElement = $dom->createElement('struct');

        if ($object instanceof TmContainer) {
            $vars = $object->toArray();
        } elseif ($object instanceof \stdClass) {
            $vars = (array)$object;
        } else {
            $vars = get_object_vars($object);
        }

        foreach ($vars as $key => $value) {

            if (\is_string($key) && str_starts_with($key, '__')) {
                continue;
            }

            $memberElement = $dom->createElement('member');
            $nameElement = $dom->createElement('name', htmlspecialchars((string)$key, ENT_XML1, 'UTF-8'));
            $valueElement = $dom->createElement('value');
            $typeElement = self::serialize($value, $dom);
            $valueElement->appendChild($typeElement);
            $memberElement->appendChild($nameElement);
            $memberElement->appendChild($valueElement);
            $structElement->appendChild($memberElement);
        }
        return $structElement;
    }

    /**
     * Deserializes an XML-RPC <array> element.
     *
     * @param  DOMElement $element <array> element to deserialize
     * @return array      corresponding PHP array
     */
    private static function deserializeArray(DOMElement $element): array
    {
        $dataElement = null;
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'data') {
                $dataElement = $child;
                break;
            }
        }

        if (!$dataElement) {
            return [];
        }

        $result = [];
        foreach ($dataElement->childNodes as $valueNode) {
            if ($valueNode instanceof DOMElement && $valueNode->tagName === 'value') {
                $found = false;
                foreach ($valueNode->childNodes as $typeNode) {
                    if ($typeNode instanceof DOMElement) {
                        $result[] = self::deserialize($typeNode);
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    // <value/> with no type -> null
                    $result[] = null;
                }
            }
        }

        return $result;
    }

    /**
     * Deserializes an XML-RPC <struct> element.
     *
     * @param  DOMElement  $element <struct> element to deserialize
     * @return TmContainer corresponding TmContainer
     */
    private static function deserializeStruct(DOMElement $element): TmContainer
    {
        $result = [];

        foreach ($element->childNodes as $memberNode) {
            if ($memberNode instanceof DOMElement && $memberNode->tagName === 'member') {
                $name = null;
                $value = null;

                foreach ($memberNode->childNodes as $childNode) {
                    if ($childNode instanceof DOMElement) {
                        if ($childNode->tagName === 'name') {
                            $name = trim($childNode->textContent);
                        } elseif ($childNode->tagName === 'value') {
                            foreach ($childNode->childNodes as $typeNode) {
                                if ($typeNode instanceof DOMElement) {
                                    $value = self::deserialize($typeNode);
                                    break;
                                }
                            }
                        }
                    }
                }

                if ($name !== null) {
                    $result[$name] = $value;
                }
            }
        }

        return TmContainer::fromArray($result);
    }

    /**
     * Try to decode base64 safely — returns null on failure.
     */
    private static function safeBase64Decode(string $text): ?string
    {
        $decoded = base64_decode($text, true);
        if ($decoded === false) {
            return null;
        }
        return $decoded;
    }

    /**
     * Flexible dateTime deserialization
     */
    private static function deserializeDateTime(string $text): ?\DateTimeImmutable
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        // Try strict XML-RPC format: 19980717T14:08:55
        $dt = \DateTimeImmutable::createFromFormat('Ymd\TH:i:s', $text);
        if ($dt !== false) {
            return $dt;
        }

        // Try common variants (with timezone or separators) using DateTimeImmutable constructor
        try {
            return new \DateTimeImmutable($text);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
