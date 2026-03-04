<?php

declare(strict_types=1);

namespace Yuha\Trna\Infrastructure\Gbx;

use Yuha\Trna\Service\Socket;

final class GbxConnection
{
    private const MAX_RES_SIZE = 4096 * 1024;
    private const TIMEOUT = 20.0;
    private int $reqHandle = 0x80000000;

    public function __construct(private Socket $socket)
    {
        $this->socket->setTimeout(self::TIMEOUT);
        $this->handshake();
    }

    public function close(): void
    {
        $this->socket->close();
    }

    public function waitFor(int $expectedHandle): string
    {
        do {
            $header = $this->socket->read(8);
            if (!$header || \strlen($header) !== 8) {
                throw new \Exception('Transport error - cannot read header');
            }

            $data = unpack('Vsize/Vhandle', $header);
            $size = $data['size'];
            $handle = $this->convertHandle($data['handle']);

            if ($size > self::MAX_RES_SIZE) {
                throw new \Exception('Transport error - response too large');
            }

            $body = $this->readExact($size);

        } while ($handle !== $expectedHandle);

        return $body;
    }

    public function send(string $xml): int
    {
        $this->reqHandle++;

        $bytes = pack('VVA*', \strlen($xml), $this->reqHandle, $xml);
        $this->socket->write($bytes);

        return $this->reqHandle;
    }

    public function readCallbackPacket(): ?string
    {
        $header = $this->socket->read(8);
        if (!$header || \strlen($header) !== 8) {
            return null;
        }

        $data = unpack('Vsize/Vhandle', $header);
        $size = $data['size'];
        $handle = $this->convertHandle($data['handle']);

        if (($handle & 0x80000000) !== 0) {
            return null; // not a callback
        }

        return $this->readExact($size);
    }

    private function readExact(int $size): string
    {
        $buffer = '';
        while (\strlen($buffer) < $size) {
            $chunk = $this->socket->read($size - \strlen($buffer));
            if ($chunk === '') {
                throw new \Exception('Transport error - reading contents');
            }
            $buffer .= $chunk;
        }
        return $buffer;
    }

    private function convertHandle(int $handle): int
    {
        $bits = \sprintf('%b', $handle);
        return \strlen($bits) === 64
            ? (int)bindec(substr($bits, 32))
            : $handle;
    }

    private function handshake(): void
    {
        $header = $this->socket->read(4);
        $size = unpack('Vsize', $header)['size'];
        $handshake = $this->socket->read($size);

        if (trim($handshake) !== 'GBXRemote 2') {
            throw new \Exception("Unsupported protocol: '{$handshake}'");
        }
    }
}
