<?php

declare(strict_types=1);

namespace Yuha\Trna\Service;

final class Socket
{
    private string $host = '127.0.0.1';
    private int $port = 5009;
    private float $timeout = 60.0;
    private int $errCode = 0;
    private string $errMessage = '';
    /** @var resource|false  $socket */
    public $socket;

    public function __construct()
    {
        $this->socket = stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $this->errCode,
            $this->errMessage,
            $this->timeout,
        );
    }

    /**
     * Sets the timeout for socket operations
     */
    public function setTimeout(float $timeout): void
    {
        $sec  = (int) $timeout;
        $usec = (int) (($timeout - $sec) * 1_000_000);

        stream_set_timeout($this->socket, $sec, $usec);
    }

    public function write(string $data): false|int
    {
        if (!\is_resource($this->socket)) {
            return false;
        }

        $written = 0;
        $len = \strlen($data);

        while ($written < $len) {
            $chunk = fwrite($this->socket, substr($data, $written));

            if ($chunk === false) {
                return false;
            }
            if ($chunk === 0) {
                break;
            }
            $written += $chunk;
        }

        return $written;
    }

    /**
     * Reads a line from the socket
     */
    public function gets(int $len = 1024): ?string
    {
        if (!\is_resource($this->socket)) {
            return null;
        }

        $line = fgets($this->socket, $len);

        if ($line === false) {
            return null;
        }

        return $line;
    }

    /**
     * Reads from the socket
     */
    public function read(int $len = 1024): ?string
    {
        if (!\is_resource($this->socket)) {
            return null;
        }

        $data = fread($this->socket, $len);

        if ($data === false) {
            return null;
        }

        if ($data === "") {
            return null;
        }

        return $data;
    }

    /**
     * Closes the socket connection
     */
    public function close(): void
    {
        if (\is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->socket = false;
    }

    public function __destruct()
    {
        $this->close();
    }
}
