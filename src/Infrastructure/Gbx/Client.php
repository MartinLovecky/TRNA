<?php

declare(strict_types=1);

namespace Yuha\Trna\Infrastructure\Gbx;

use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Infrastructure\Xml\{Request, Response};
use Yuha\Trna\Service\{Socket, WidgetBuilder};

class Client
{
    private const int MAX_REQ_SIZE = 512 * 1024 - 8;
    private const int MAX_RES_SIZE = 4096 * 1024;
    private const float TIMEOUT = 20.0;
    public array $calls = [];
    public string $methodName = '';
    private int $reqHandle = 0x80000000;
    /** @var TmContainer[] */
    private array $cb_message = [];

    public function __construct(
        private Socket $socket,
        private Request $request,
        private Response $response,
        private WidgetBuilder $widgetBuilder
    ) {
        $this->socket->setTimeout(self::TIMEOUT);
        $this->init();
        $this->query('Authenticate', [$_ENV['admin_login'], $_ENV['admin_password']]);
    }

    public function query(string $method, array $params = []): TmContainer
    {
        $xmlString = $this->request->createRpcRequest($method, $params);

        if (\strlen($xmlString) > self::MAX_REQ_SIZE) {
            throw new \Exception("Transport error - request too large {$method}");
        }

        if (!$this->sendRequest($xmlString)) {
            throw new \Exception('Transport error - connection interrupted');
        }

        $result = $this->result($method);

        if ($result->has('result.faultString')) {
            throw new \Exception("{$method} error {$result->get('faultString')}{$result->get('faultCode')}");
        }

        return $result;
    }

    /**
     * Multi-call RPC
     *
     * $calls = [
     *   ['methodName' => 'Authenticate', 'params' => ['user', 'pass']],
     *   ['methodName' => 'GetStatus', 'params' => []],
     * ]
     */
    public function multicall(array $calls)
    {
        $xml = $this->request->createMultiCallRequest($calls);
        $this->reqHandle++;
        $handle = $this->reqHandle;

        $bytes = pack('VVA*', \strlen($xml), $handle, $xml);

        $this->socket->write($bytes);
    }

    public function readCallBack(float $timeout = 2.0): bool
    {
        $somethingReceived = !empty($this->cb_message);

        $this->socket->setTimeout($timeout);

        $read = [$this->socket->socket];
        $write = null;
        $expect = null;

        $timeoutSeconds = (int)$timeout;
        $timeoutUsec = (int)(($timeout - $timeoutSeconds) * 1_000_000);

        $available = @stream_select(
            $read,
            $write,
            $expect,
            $timeoutSeconds,
            $timeoutUsec,
        );

        while ($available !== false && $available > 0) {
            $size = 0;
            $recvHandle = 0;

            $contents = $this->readContents(8);
            if ($contents !== '') {
                $data = unpack('Vsize/Vhandle', $contents);
                $size = $data['size'];
                $recvHandle = $this->convertHandle($data['handle']);
            }

            if ($recvHandle === 0 || $size === 0) {
                throw new \Exception('transport error - connection interrupted');
            }

            if ($size > self::MAX_RES_SIZE) {
                throw new \Exception('transport error - response too large');
            }

            $contents = $this->readContents($size);

            if (\strlen($contents) < $size) {
                throw new \Exception('transport error - failed to read full response');
            }

            if (($recvHandle & 0x80000000) === 0) {
                $this->cb_message[] = $this->response->processCallback($contents);
            }

            $read = [$this->socket->socket];
            $available = @stream_select($read, $write, $except, 0, 0);
        }

        return $somethingReceived;
    }

    public function popCBResponse(): ?TmContainer
    {
        return array_shift($this->cb_message) ?: null;
    }

    public function terminate(): void
    {
        $this->socket->close();
    }

    private function sendRequest(string $xml): bool
    {
        if (!\is_resource($this->socket->socket)) {
            return false;
        }

        $this->reqHandle++;

        $bytes = pack('VVA*', \strlen($xml), $this->reqHandle, $xml);

        return $this->socket->write($bytes) !== 0;
    }

    private function result(string $method): TmContainer
    {
        $contents = '';

        if (\is_resource($this->socket->socket)) {
            do {
                $size = 0;
                $recvHandle = 0;
                $this->socket->setTimeout(self::TIMEOUT);
                $contents = $this->socket->read(8);

                if ($contents === false) {
                    throw new \Exception('Transport error - cannot read socket');
                }
                if ($contents === '') {
                    throw new \Exception('Transport error - cannot read size');
                }

                $result = unpack('Vsize/Vhandle', $contents);
                $size = $result['size'];
                $recvHandle = $this->convertHandle($result['handle']);

                if ($recvHandle === 0 || $size === 0) {
                    throw new \Exception('Transport error - connection interrupted');
                }

                if ($size > self::MAX_RES_SIZE) {
                    throw new \Exception("Transport error - response too large {$method}");
                }

                $contents = $this->readContents($size);
                if (($recvHandle & 0x80000000) === 0) {
                    $this->cb_message[] = $this->response->processCallback($contents);
                }
            } while ($recvHandle !== $this->reqHandle);

            return $this->response->processResponse($method, $contents);
        }

        return TmContainer::fromArray();
    }

    private function convertHandle(int $handle): int
    {
        $bits = \sprintf('%b', $handle);
        return (\strlen($bits) === 64) ? (int)bindec(substr($bits, 32)) : $handle;
    }

    public function sendXmlToAll(string $xml, int $timeout = 0, bool $hide = false): void
    {
        $this->query('SendDisplayManialinkPage', [$xml, $timeout, $hide]);
    }

    public function sendXmlToLogin(
        string $login,
        string $xml,
        int $timeout = 0,
        bool $hide = false
    ): void {
        $this->query('SendDisplayManialinkPageToLogin', [$login, $xml, $timeout, $hide]);
    }

    public function sendRenderToAll(
        string $template,
        array $context = [],
        int $timeout = 0,
        bool $hide = false
    ): void {
        $xml = $this->widgetBuilder->render($template, $context);
        $this->sendXmlToAll($xml, $timeout, $hide);
    }

    public function sendRenderToLogin(
        string $login,
        string $template,
        array $context = [],
        int $timeout = 0,
        bool $hide = false
    ): void {
        $xml = $this->widgetBuilder->render($template, $context);
        $this->sendXmlToLogin($login, $xml, $timeout, $hide);
    }

    public function sendChatMessageToAll(string $msg): void
    {
        $this->query('ChatSendServerMessage', [$msg]);
    }

    public function sendChatMessageToLogin(string $message, string $login): void
    {
        $this->query('ChatSendServerMessageToLogin', [$message, $login]);
    }

    private function readContents(int $size): string
    {
        $contents = '';

        if (\is_resource($this->socket->socket)) {
            $this->socket->setTimeout(0.10);
            while (\strlen($contents) < $size) {
                $chunk = $this->socket->read($size - \strlen($contents));
                if ($chunk === '') {
                    throw new \Exception('Transport error - reading contents');
                }
                $contents .= $chunk;
            }
        }

        return $contents;
    }

    private function init(): void
    {
        $handshake = '';

        if (\is_resource($this->socket->socket)) {
            $header = $this->socket->read(4);

            if ($header === false || \strlen($header) !== 4) {
                throw new \Exception('Transport error - failed to read protocol header');
            }

            $unpacked = @unpack('Vsize', $header);
            if ($unpacked === false || !isset($unpacked['size'])) {
                throw new \Exception('Transport error - failed to unpack protocol header');
            }

            $size = $unpacked['size'];

            if ($size > 64) {
                throw new \Exception('Transport error - wrong low-level protocol header');
            }

            $handshake = $this->socket->read($size);
            if ($handshake === false || \strlen($handshake) !== $size) {
                throw new \Exception('Transport error - failed to read handshake');
            }

            if (trim($handshake) !== 'GBXRemote 2') {
                throw new \Exception("Unsupported protocol: '{$handshake}'. Only GBXRemote 2 is supported.");
            }
        }
    }
}
