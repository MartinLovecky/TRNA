<?php

declare(strict_types=1);

namespace Yuha\Trna\Infrastructure\Xml;

use DOMDocument;
use DOMElement;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Service\Internal\{Arr, CallbackHelper};

/**
 * This class handles parsing of XML-RPC responses.
 * It supports both single and multi-call responses and maps them into TmContainer objects.
 *
 * @author Yuhzel <yuha@gmail.com>
 */
class Response
{
    use LoggerAware;
    private string $methodName = '';

    public function __construct(private DOMDocument $dom)
    {
        $this->dom->encoding = 'UTF-8';
        $this->dom->formatOutput = true;
        $this->dom->xmlVersion = '1.0';
        $this->dom->preserveWhiteSpace = false;
        $this->initLog('Response');
    }

    /**
     * parse XML-RPC callback.
     *
     * @param  string      $xml XML string representing the method call
     * @throws \Exception  If parsing fails
     * @return TmContainer Parsed container with methodName and params
     */
    public function processCallback(string $xml): TmContainer
    {
        if (!$this->dom->loadXML($xml, LIBXML_NOCDATA | LIBXML_NOWARNING)) {
            throw new \Exception('Failed to parse XML method call.');
        }

        $root = $this->dom->documentElement;
        if ($root->tagName !== 'methodCall') {
            throw new \Exception('Expected <methodCall> root element.');
        }

        $methodNameElement = $this->getFirstDirectChild($root, 'methodName');
        if (!$methodNameElement) {
            throw new \Exception('Missing <methodName> element in method call.');
        }

        $this->methodName = trim($methodNameElement->textContent);
        $c = TmContainer::fromArray(['methodName' => $this->methodName]);

        $paramsElement = $this->getFirstDirectChild($root, 'params');
        if (!$paramsElement) {
            return $c;
        }

        $paramElements = $this->getDirectChildren($paramsElement, 'param');
        $paramNames = CallbackHelper::getNamedParams($this->methodName);

        foreach ($paramElements as $index => $param) {
            // skip index 3
            if ($index === 3) {
                continue;
            }
            $valueElement = $this->getFirstDirectChild($param, 'value');
            $path = $paramNames[$index] ?? (string)$index;
            $c->set($path, RpcConverter::deserialize($valueElement));

            // Log to implemt friendly names for parameters
            if (!isset($paramNames[$index])) {
                $this->logInfo(
                    "Discovered unknown callback parameter for {$this->methodName} at index $index",
                    ['value' => $valueElement->textContent],
                );
                $paramNames[$index] = "param$index";
                CallbackHelper::setMapping($this->methodName, $paramNames);
            }
        }

        return $c;
    }

    /**
     * parse XML-RPC response.
     *
     * @param  string      $methodName Name of the method being responded to
     * @param  string      $xml        XML response content
     * @param  bool        $multicall  set true to procces multiCallRequest
     * @throws \Exception  If the XML is malformed or missing required elements
     * @return TmContainer Parsed container object with the response data
     */
    public function processResponse(string $methodName, string $xml, bool $multicall = false): TmContainer
    {
        $this->methodName = $methodName;

        if (!$this->dom->loadXML($xml, LIBXML_NOCDATA | LIBXML_NOWARNING)) {
            throw new \Exception('Failed to parse XML response.');
        }

        $root = $this->dom->documentElement;
        $faultElement = $this->getFirstDirectChild($root, 'fault');

        if ($faultElement) {
            $valueElement = $this->getFirstDirectChild($faultElement, 'value');
            $fault = RpcConverter::deserialize($valueElement);
            $this->logError(
                "Received fault response for {$this->methodName}",
                ['fault' => $fault],
            );
            return $fault;
        }

        $paramsElement = $this->getFirstDirectChild($root, 'params');

        return $multicall
            ? $this->processMultiCall($paramsElement)
            : $this->processParams($paramsElement);
    }

    /**
     * Processes a standard <params> response.
     *
     * @param  DOMElement  $params The <params> element
     * @return TmContainer Updated container with parsed parameter values
     */
    private function processParams(DOMElement $params): TmContainer
    {
        if (!$params) {
            throw new \Exception("Missing <params> element in response {$this->methodName}.");
        }

        $paramElements = $this->getDirectChildren($params, 'param');

        $x = ['methodName' => $this->methodName];
        //NOTE (yuha) We could use idea of CallbackHelper here as well, but it would be a lot of work
        // to map all the responses, so for now we just use numeric indexes
        foreach ($paramElements as $index => $param) {
            $valueElement = $this->getFirstDirectChild($param, 'value');
            $proccessedValue = RpcConverter::deserialize($valueElement);

            $x['result'] = $proccessedValue;
        }

        return TmContainer::fromArray($x);
    }

    /**
     * Processes system.multicall
     *
     * @param  DOMElement  $params The <params> element containing multiple responses
     * @return TmContainer TmContainer with responses keyed by index
     */
    private function processMultiCall(DOMElement $params): TmContainer
    {
        $paramElements = $this->getDirectChildren($params, 'param');
        $x = [];
        foreach ($paramElements as $index => $param) {
            $valueElement = $this->getFirstDirectChild($param, 'value');
            $x[(string)$index] = RpcConverter::deserialize($valueElement);
        }
        $x['methodName'] = $this->methodName;
        $result = ['results' => Arr::flatten($x)];
        return TmContainer::fromArray($result);
    }

    /**
     * Efficiently gets the first direct child element with the given tag name.
     *
     * @param  DOMElement      $parent  Parent element
     * @param  string          $tagName Child tag name to search for
     * @return DOMElement|null First matching child element or null if none found
     */
    private function getFirstDirectChild(DOMElement $parent, string $tagName): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $tagName) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Returns all direct child elements of a parent with a given tag name.
     *
     * @param  DOMElement   $parent  Parent element
     * @param  string       $tagName Tag name to search for
     * @return DOMElement[] Array of matched child elements
     */
    private function getDirectChildren(DOMElement $parent, string $tagName): array
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $tagName) {
                $children[] = $child;
            }
        }

        return $children;
    }
}
