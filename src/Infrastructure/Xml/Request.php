<?php

declare(strict_types=1);

namespace Yuha\Trna\Infrastructure\Xml;

use DOMDocument;
use DOMElement;

/**
 * Request is responsible for creating XML-RPC
 * @author Yuhzel <yuhzel@gmail.com>
 */
class Request
{
    protected DOMDocument $dom;

    /**
     * Creates an XML-RPC request for a single method call.
     *
     * @param  string $methodName Name of the method to call
     * @param  array  $args       Arguments to pass to the method
     * @return string XML-RPC string representing the request
     */
    public function createRpcRequest(string $methodName, array $args): string
    {
        $this->dom = new DOMDocument();
        $this->dom->encoding = 'UTF-8';
        $this->dom->formatOutput = true;
        $this->dom->xmlVersion = '1.0';
        $this->dom->preserveWhiteSpace = false;
        $methodCall = $this->dom->createElement('methodCall');
        $this->dom->appendChild($methodCall);
        $methodName = $this->dom->createElement('methodName', htmlspecialchars($methodName, ENT_XML1, 'UTF-8'));
        $methodCall->appendChild($methodName);
        $params = $this->dom->createElement('params');
        $methodCall->appendChild($params);

        foreach ($args as $arg) {
            $this->addParam($arg, $params);
        }

        return $this->dom->saveXML();
    }

    /**
     * creates an XML-RPC request for multiple method calls (system.multicall).
     *
     * @param  array  $calls Array of method calls, with 'methodName' and 'params'
     * @return string string XML-RPC string representing the multicall request
     */
    public function createMultiCallRequest(array $calls): string
    {
        $this->dom = new \DOMDocument();
        $this->dom->encoding = 'UTF-8';
        $this->dom->formatOutput = true;
        $this->dom->xmlVersion = '1.0';
        $this->dom->preserveWhiteSpace = false;

        // Root <methodCall>
        $methodCall = $this->dom->createElement('methodCall');
        $this->dom->appendChild($methodCall);

        // <methodName>system.multicall</methodName>
        $methodName = $this->dom->createElement('methodName', 'system.multicall');
        $methodCall->appendChild($methodName);

        // <params>
        $params = $this->dom->createElement('params');
        $methodCall->appendChild($params);

        // Single <param> containing the multicall array
        $param = $this->dom->createElement('param');
        $valueElement = $this->dom->createElement('value');
        $arrayElement = $this->dom->createElement('array');
        $dataElement = $this->dom->createElement('data');

        foreach ($calls as $call) {
            // Each call is a <struct>
            $callStruct = $this->dom->createElement('struct');

            // --- methodName member ---
            $memberMethod = $this->dom->createElement('member');
            $nameMethod = $this->dom->createElement('name', 'methodName');
            $valueMethod = $this->dom->createElement('value');
            $valueMethod->appendChild($this->dom->createTextNode($call['methodName']));
            $memberMethod->appendChild($nameMethod);
            $memberMethod->appendChild($valueMethod);
            $callStruct->appendChild($memberMethod);

            // --- params member ---
            $memberParams = $this->dom->createElement('member');
            $nameParams = $this->dom->createElement('name', 'params');
            $paramsValue = $this->dom->createElement('value');
            $paramsArray = $this->dom->createElement('array');
            $paramsData = $this->dom->createElement('data');

            foreach ($call['params'] as $arg) {
                $val = $this->dom->createElement('value');
                $val->appendChild(RpcConverter::serialize($arg, $this->dom));
                $paramsData->appendChild($val);
            }

            $paramsArray->appendChild($paramsData);
            $paramsValue->appendChild($paramsArray);
            $memberParams->appendChild($nameParams);
            $memberParams->appendChild($paramsValue);
            $callStruct->appendChild($memberParams);

            // Wrap struct in <value> and add to <data>
            $valueItem = $this->dom->createElement('value');
            $valueItem->appendChild($callStruct);
            $dataElement->appendChild($valueItem);
        }

        $arrayElement->appendChild($dataElement);
        $valueElement->appendChild($arrayElement);
        $param->appendChild($valueElement);
        $params->appendChild($param);

        return $this->dom->saveXML();
    }

    /**
     * Adds a parameter to the params element.
     *
     * @param mixed      $arg    Parameter value
     * @param DOMElement $params Parent params element
     */
    public function addParam(mixed $arg, DOMElement $params): void
    {
        $param = $this->dom->createElement('param');
        $valueElement = $this->dom->createElement('value');
        $typeElement = RpcConverter::serialize($arg, $this->dom);
        $valueElement->appendChild($typeElement);
        $param->appendChild($valueElement);
        $params->appendChild($param);
    }
}
