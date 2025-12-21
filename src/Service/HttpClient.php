<?php

declare(strict_types=1);

namespace Yuha\Trna\Service;

use CurlHandle;
use Yuha\Trna\Core\Server;
use Yuha\Trna\Core\Traits\LoggerAware;

class HttpClient
{
    use LoggerAware;

    /**
     * @var CurlHandle|null cURL handle.
     */
    private ?CurlHandle $ch = null;

    /**
     * @var string Path to the certificate file.
     */
    private string $cert;

    /**
     * @var string Path to the file where cookies are stored.
     */
    private string $cookieFile;

    /**
     * @var array Default headers to send with every request
     */
    private array $defaultHeaders = [];

    /**
     * @var array Base options that will be re-applied after reset
     */
    private array $baseOptions = [];

    /**
     * @var bool Whether debug mode is enabled
     */
    private bool $debugEnabled = false;

    /**
     * @var resource|null Debug file pointer
     */
    private $debugFp = null;

    public function __construct()
    {
        $this->initLog('HttpClient');
        $this->cert = Server::$publicDir . 'cacert.pem';
        $this->cookieFile = Server::$publicDir . 'cookies.txt';
        $this->initializeCurl();
        $this->addDefaultHeader(
            'Content-Type',
            'application/x-www-form-urlencoded; charset=UTF-8',
        );
    }

    /**
     * Initialize cURL handle
     */
    private function initializeCurl(): void
    {
        $this->ch = curl_init();

        $this->baseOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CAINFO => $this->cert,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_ENCODING => '',
            CURLOPT_FORBID_REUSE => false,
            CURLOPT_TCP_KEEPALIVE => 1,
            CURLOPT_TCP_KEEPIDLE => 60,
            CURLOPT_TCP_KEEPINTVL => 10,
            CURLOPT_KEEP_SENDING_ON_ERROR => true,
            CURLOPT_MAXCONNECTS => 10,
            CURLOPT_MAXAGE_CONN => 300,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT => 'YuhaTrnaHttpClient/1.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_AUTOREFERER => true,
        ];

        $this->applyBaseOptions();
    }

    /**
     * Apply base options to cURL handle
     */
    private function applyBaseOptions(): void
    {
        if ($this->ch === null) {
            $this->initializeCurl();
        }

        foreach ($this->baseOptions as $option => $value) {
            curl_setopt($this->ch, $option, $value);
        }

        // Re-apply debug settings if enabled
        if ($this->debugEnabled && $this->debugFp) {
            curl_setopt($this->ch, CURLOPT_VERBOSE, true);
            curl_setopt($this->ch, CURLOPT_STDERR, $this->debugFp);
        }

        $sh = curl_share_init_persistent([
            CURL_LOCK_DATA_DNS,
            CURL_LOCK_DATA_CONNECT,
        ]);
        curl_setopt($this->ch, CURLOPT_SHARE, $sh);
    }

    /**
     * Reset cURL handle to base state between requests
     */
    private function resetForRequest(): void
    {
        if ($this->ch === null) {
            $this->initializeCurl();
            return;
        }

        // Reset handle to clean state
        curl_reset($this->ch);

        // Re-apply base options
        $this->applyBaseOptions();

        // Apply default headers if set
        if (!empty($this->defaultHeaders)) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->defaultHeaders);
        }
    }

    /**
     * Get cURL handle, initializing if needed
     */
    private function getCurlHandle(): CurlHandle
    {
        if ($this->ch === null) {
            $this->initializeCurl();
        }

        return $this->ch;
    }

    /**
     * Set default headers for all requests
     */
    public function setDefaultHeaders(array $headers): void
    {
        $this->defaultHeaders = $headers;
    }

    /**
     * Add a default header
     */
    public function addDefaultHeader(string $name, string $value): void
    {
        $this->defaultHeaders[] = "{$name}: {$value}";
    }

    public function get(string $endpoint, array $params = [], array $headers = []): string|bool
    {
        return $this->request('GET', $endpoint, $params, $headers);
    }

    public function post(string $endpoint, string|array $data = [], array $headers = []): string|bool
    {
        return $this->request('POST', $endpoint, $data, $headers);
    }

    public function put(string $endpoint, array $data = [], array $headers = []): string|bool
    {
        return $this->request('PUT', $endpoint, $data, $headers);
    }

    public function delete(string $endpoint, array $data = [], array $headers = []): string|bool
    {
        return $this->request('DELETE', $endpoint, $data, $headers);
    }

    /**
     * Send XML data via POST request
     */
    public function postXml(string $endpoint, string $xml, string $userAgent = 'YuhaTrnaHttpClient/1.0'): string|bool
    {
        $headers = [
            'User-Agent: ' . $userAgent,
            'Content-Type: text/xml; charset=UTF-8',
            'Content-Length: ' . \strlen($xml),
            'Cache-Control: no-cache',
            'Accept-Encoding: gzip, deflate',
            'Connection: Keep-Alive',
            'Keep-Alive: timeout=600, max=2000',
        ];

        return $this->post($endpoint, $xml, $headers);
    }

    /**
     * Check if a connection to endpoint is alive using HEAD request
     * This preserves connection reuse capability
     */
    public function alive(string $endpoint = ''): bool
    {
        if (empty($endpoint)) {
            return false;
        }

        $this->resetForRequest();
        $ch = $this->getCurlHandle();

        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->logError('Connection check error: ' . curl_error($ch), [
                'endpoint' => $endpoint,
            ]);
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $httpCode >= 200 && $httpCode < 400;
    }

    /**
     * Check if keep-alive is working by comparing connection info between requests
     */
    public function isKeepAliveWorking(string $endpoint): bool
    {
        $this->resetForRequest();
        $ch = $this->getCurlHandle();

        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $result1 = curl_exec($ch);

        $info1 = [
            'local_port' => curl_getinfo($ch, CURLINFO_LOCAL_PORT),
            'primary_port' => curl_getinfo($ch, CURLINFO_PRIMARY_PORT),
            'connect_time' => curl_getinfo($ch, CURLINFO_CONNECT_TIME),
        ];

        $this->resetForRequest();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $result2 = curl_exec($ch);

        $info2 = [
            'local_port' => curl_getinfo($ch, CURLINFO_LOCAL_PORT),
            'primary_port' => curl_getinfo($ch, CURLINFO_PRIMARY_PORT),
            'connect_time' => curl_getinfo($ch, CURLINFO_CONNECT_TIME),
        ];

        $isReused = ($info1['local_port'] === $info2['local_port']) &&
            ($info1['local_port'] !== 0);

        $this->logDebug('Keep-alive check', [
            'endpoint' => $endpoint,
            'connection_reused' => $isReused,
            'first_request' => $info1,
            'second_request' => $info2,
            'connect_time_saved' => $info1['connect_time'] > 0 && $info2['connect_time'] < $info1['connect_time'] ? 'Yes' : 'No',
        ]);

        return $isReused;
    }

    /**
     * Get connection statistics
     */
    public function getConnectionStats(): array
    {
        $ch = $this->getCurlHandle();

        return [
            'local_port' => curl_getinfo($ch, CURLINFO_LOCAL_PORT),
            'primary_port' => curl_getinfo($ch, CURLINFO_PRIMARY_PORT),
            'primary_ip' => curl_getinfo($ch, CURLINFO_PRIMARY_IP),
            'connect_time' => curl_getinfo($ch, CURLINFO_CONNECT_TIME),
            'namelookup_time' => curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME),
            'pretransfer_time' => curl_getinfo($ch, CURLINFO_PRETRANSFER_TIME),
            'starttransfer_time' => curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME),
            'total_time' => curl_getinfo($ch, CURLINFO_TOTAL_TIME),
            'size_upload' => curl_getinfo($ch, CURLINFO_SIZE_UPLOAD),
            'size_download' => curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD),
            'speed_download' => curl_getinfo($ch, CURLINFO_SPEED_DOWNLOAD),
            'speed_upload' => curl_getinfo($ch, CURLINFO_SPEED_UPLOAD),
            'redirect_count' => curl_getinfo($ch, CURLINFO_REDIRECT_COUNT),
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'effective_url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
        ];
    }

    public function setTimeout(int $seconds): void
    {
        $this->baseOptions[CURLOPT_TIMEOUT] = $seconds;
        $this->applyBaseOptions();
    }

    /**
     * Set connection timeout
     */
    public function setConnectTimeout(int $seconds): void
    {
        $this->baseOptions[CURLOPT_CONNECTTIMEOUT] = $seconds;
        $this->applyBaseOptions();
    }

    /**
     * Enable verbose logging for debugging
     */
    public function enableDebug(?string $logFile = null): void
    {
        $this->debugEnabled = true;

        if ($logFile === null) {
            $logFile = Server::$logsDir . 'httpclient-debug-' . date('Y-m-d_H-i-s') . '.log';
        }

        if ($logFile) {
            if ($this->debugFp) {
                fclose($this->debugFp);
            }
            $this->debugFp = fopen($logFile, 'a+');

            if ($this->debugFp) {
                curl_setopt($this->getCurlHandle(), CURLOPT_VERBOSE, true);
                curl_setopt($this->getCurlHandle(), CURLOPT_STDERR, $this->debugFp);
            }
        }
    }

    /**
     * Disable verbose logging
     */
    public function disableDebug(): void
    {
        $this->debugEnabled = false;

        if ($this->debugFp) {
            fclose($this->debugFp);
            $this->debugFp = null;
        }

        curl_setopt($this->getCurlHandle(), CURLOPT_VERBOSE, false);
        curl_setopt($this->getCurlHandle(), CURLOPT_STDERR, null);
    }

    /**
     * Perform HTTP request
     */
    private function request(string $method, string $endpoint, string|array $params = [], array $headers = []): string|bool
    {
        $this->resetForRequest();
        $ch = $this->getCurlHandle();

        if ($method === 'GET' && !empty($params) && \is_array($params)) {
            $endpoint .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if (\in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            if (\is_string($params)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            } elseif (\is_array($params)) {
                $isJson = false;
                foreach ($headers as $header) {
                    if (stripos($header, 'Content-Type: application/json') !== false) {
                        $isJson = true;
                        break;
                    }
                }

                if ($isJson) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                }
            }
        }

        $allHeaders = array_merge($this->defaultHeaders, $headers);
        if (!empty($allHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorNo = curl_errno($ch);
        $localPort = curl_getinfo($ch, CURLINFO_LOCAL_PORT);

        if ($errorNo) {
            $this->logError('cURL error: ' . curl_error($ch), [
                'endpoint' => $endpoint,
                'method' => $method,
                'error_no' => $errorNo,
                'http_code' => $httpCode,
                'localPort' => $localPort,
            ], 'HttpClient');
            return false;
        }

        $successCodes = [200, 201, 202, 204, 301, 302, 304];
        if (!\in_array($httpCode, $successCodes, true)) {
            $this->logError('HTTP error: ' . $httpCode, [
                'endpoint' => $endpoint,
                'method' => $method,
                'http_code' => $httpCode,
                'response' => substr((string)$response, 0, 500),
            ], 'HttpClient');
            return false;
        }

        return $response;
    }

    /**
     * Set a custom User-Agent string for all requests
     */
    public function setUserAgent(string $userAgent): void
    {
        $this->baseOptions[CURLOPT_USERAGENT] = $userAgent;

        // Apply immediately if cURL handle exists
        if ($this->ch !== null) {
            curl_setopt($this->ch, CURLOPT_USERAGENT, $userAgent);
        }
    }

    /**
     * Close cURL handle explicitly
     */
    public function close(): void
    {
        if ($this->ch !== null) {
            $this->ch = null;
        }

        if ($this->debugFp) {
            fclose($this->debugFp);
            $this->debugFp = null;
        }
    }

    /**
     * Clean up resources
     */
    public function __destruct()
    {
        $this->close();
    }
}
