# HttpClient Library Documentation

## Overview

The `HttpClient` class provides a simple, lightweight, and feature-rich interface for making HTTP requests in PHP. It supports GET, POST, PUT, DELETE requests, persistent connections (keep-alive), custom headers, timeouts, debugging, and session/cookie management.

This documentation covers core usage and advanced features.

---

# Basic Usage

## Create Client

```php
$client = new HttpClient();
```

## Simple GET Request

```php
$response = $client->get('https://api.example.com/users');
if ($response !== false) {
    $users = json_decode($response, true);
    echo "Fetched " . count($users) . " users\n";
}
```

## GET With Query Parameters

```php
$response = $client->get('https://api.example.com/users', [
    'page' => 1,
    'limit' => 20,
    'sort' => 'name'
]);
```

## POST Request (Form Data)

```php
$response = $client->post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secret123'
]);
```

## POST Request (JSON Data)

```php
$response = $client->post(
    'https://api.example.com/users',
    json_encode([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com'
    ]),
    ['Content-Type: application/json']
);
```

## PUT Request

```php
$response = $client->put('https://api.example.com/users/123', [
    'name' => 'Updated Name',
    'email' => 'updated@example.com'
]);
```

## DELETE Request

```php
$response = $client->delete('https://api.example.com/users/123');
```

---

# Advanced Features

## 1. Connection Keep-Alive & Performance

```php
$client = new HttpClient();

// Multiple requests → connection reused
$response1 = $client->get('https://api.example.com/users');
$response2 = $client->get('https://api.example.com/products');
$response3 = $client->get('https://api.example.com/orders');

// Check keep-alive status
$isKeepAliveWorking = $client->isKeepAliveWorking('https://api.example.com');
echo "Keep-alive working: " . ($isKeepAliveWorking ? 'Yes' : 'No') . "\n";

// Test if server connection is alive
$isAlive = $client->alive('https://api.example.com');
echo "Server is alive: " . ($isAlive ? 'Yes' : 'No') . "\n";
```

---

## 2. Default Headers

```php
$client->setDefaultHeaders([
    'Authorization: Bearer your_token_here',
    'Accept: application/json',
    'Accept-Language: en-US',
]);

// Add an additional header
$client->addDefaultHeader('X-Custom-Header', 'CustomValue');

// Requests now include these headers
$users = $client->get('https://api.example.com/users');
$products = $client->get('https://api.example.com/products');
```

---

## 3. Timeout Configuration

```php
// Set global timeout
$client->setTimeout(30); // 30 seconds

// Set connection timeout
$client->setConnectTimeout(10); // 10 seconds

// Request using timeout settings
$response = $client->get('https://slow-api.example.com/data');
```

---

## 4. Debugging & Monitoring

```php
// Enable debug log
$client->enableDebug('/tmp/http_client_debug.log');

// Make requests (logged automatically)
$response = $client->get('https://api.example.com/test');

// Retrieve connection statistics
$stats = $client->getConnectionStats();
echo "Request took: " . $stats['total_time'] . " seconds\n";
echo "Download speed: " . $stats['speed_download'] . " bytes/sec\n";
echo "Local port used: " . $stats['local_port'] . "\n";

// Disable debug
$client->disableDebug();
```

---

## 5. Session / Cookie Management

```php
// Login (cookies are saved automatically)
$response = $client->post('https://example.com/login', [
    'username' => 'user',
    'password' => 'pass'
]);

// Session cookies included automatically
$dashboard = $client->get('https://example.com/dashboard');
$profile  = $client->get('https://example.com/profile');
```

---

# Summary

The `HttpClient` class provides:

- Easy-to-use HTTP methods (GET, POST, PUT, DELETE)
- Automatic keep-alive for improved performance
- Global and per-request header control
- Timeout customization
- Debugging and connection monitoring tools
- Built-in cookie/session management

This makes it a robust utility for APIs, services, and web automation.
