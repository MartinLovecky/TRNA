<?php

declare(strict_types=1);

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    throw new \RuntimeException('This script requires PHP 8.3.0 or higher.');
}

require __DIR__ . '/vendor/autoload.php';

$container = new \League\Container\Container();
$container->delegate(new \League\Container\ReflectionContainer(true));
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Yuha\Trna\Core\Server::setPaths();

$controller = $container->get(\Yuha\Trna\Core\Controllers\AppController::class);
$fluent = $container->get(\Yuha\Trna\Repository\Fluent::class);
$structure = $container->get(\Yuha\Trna\Repository\Structure::class);

// ---------- SIGNAL HANDLING (CTRL-C) ----------
Revolt\EventLoop::onSignal(SIGINT, static function () {
    echo "\nCTRL-C detected → stopping loop...\n";
    Revolt\EventLoop::getDriver()->stop();
});

// ---------- TERMINAL MODE ----------
if (DIRECTORY_SEPARATOR === "/") { // POSIX only
    shell_exec('stty -icanon -echo');
    register_shutdown_function(static function () {
        shell_exec('stty sane');
    });
}

// ---------- ESC KEY HANDLING ----------
if (defined('STDIN')) {
    Revolt\EventLoop::onReadable(STDIN, static function () {
        $char = stream_get_contents(STDIN, 1);

        if ($char === "\e") {  // ESC key
            echo "ESC detected → stopping loop...\n";
            Revolt\EventLoop::getDriver()->stop();
        }
    });
}

// foreach (Yuha\Trna\Core\Enums\Table::cases() as $table) {
//     $fluent->executeFile($table);
//     $structure->validate($table);
// }

// ---------- RUN CONTROLLER ----------
Revolt\EventLoop::queue(static function () use ($controller) {
    echo "TRNA controller started...\n";
    $controller->run();
});

// ---------- START LOOP ----------
Revolt\EventLoop::run();
