<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Core\Contracts\DependentPlugin;
use Yuha\Trna\Core\Controllers\PluginController;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Window\WindowRegistry;
use Yuha\Trna\Infrastructure\Gbx\Client;

class ManiaLinks implements DependentPlugin
{
    private const string PATH = 'maniaLinks' . \DIRECTORY_SEPARATOR;
    private const int OUT = 0;
    private PluginController $pluginController;

    public function __construct(private Client $client)
    {
    }

    public function setRegistry(PluginController $pluginController): void
    {
        $this->pluginController = $pluginController;
    }

    public function onAnswer(TmContainer $player)
    {
        try {
            [$panel, $currentAction] = WindowRegistry::decode($player->get('encodedAction'));
            $player->set("{$panel->name}.currentPage", $currentAction);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }

        $res = $this->handleAction();
    }

    public function displayToAll(string $winName, array $context, bool $hide = false): void
    {
        $this->client->sendRenderToAll(self::PATH . $winName, $context, self::OUT, $hide);
    }

    public function displayToLogin(string $winName, string $login, array $context, bool $hide = false): void
    {
        $this->client->sendRenderToLogin($login, self::PATH . $winName, $context, self::OUT, $hide);
    }

    public function closeDisplayToAll(int $id = 1): void
    {
        $this->client->sendXmlToAll("<manialink id='{$id}'></manialink>");
    }

    public function closeDisplayToLogin(string $login, int $id = 1): void
    {
        $this->client->sendXmlToLogin($login, "<manialink id='{$id}'></manialink>");
    }

    private function handleAction()
    {
    }
}
