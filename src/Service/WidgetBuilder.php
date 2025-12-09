<?php

declare(strict_types=1);

namespace Yuha\Trna\Service;

use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Yuha\Trna\Core\Server;
use Yuha\Trna\Service\Internal\TwigFiltersExtension;

final class WidgetBuilder
{
    private Environment $twig;
    public bool $debug = true;

    public function __construct()
    {
        $loader = new FilesystemLoader(Server::$twigDir);

        $this->twig = new Environment($loader, [
            'cache' => Server::$publicDir . 'cache',
            'auto_reload' => true,
            'debug' => $this->debug,
            'strict_variables' => true,
        ]);

        $this->twig->addExtension(new TwigFiltersExtension());

        if ($this->twig->isDebug()) {
            $this->twig->addExtension(new DebugExtension());
        }
    }

    /**
     * Render a Twig template with optional context.
     *
     * @param  string $template Base template name (without extension)
     * @param  array  $context  Associative array of variables
     * @return string Rendered template content
     */
    public function render(string $template, array $context = []): string
    {
        return $this->twig->render($template . '.xml.twig', $context);
    }
}
