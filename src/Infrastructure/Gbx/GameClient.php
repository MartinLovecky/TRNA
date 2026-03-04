<?php

declare(strict_types=1);

namespace Yuha\Trna\Infrastructure\Gbx;

use Yuha\Trna\Core\Enums\RpcMethod;
use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Service\WidgetBuilder;

final class GameClient
{
    public function __construct(
        private GbxRpcClient $rpc,
        private WidgetBuilder $widgetBuilder
    ) {
    }

    public function call(RpcMethod $method, array $params = []): TmContainer
    {
        return $this->rpc->query($method->value, $params);
    }

    public function multicall(array $calls): array
    {
        $formatted = [];

        foreach ($calls as $call) {
            if (!($call[0] instanceof RpcMethod)) {
                throw new \InvalidArgumentException('First element of each call must be RpcMethod');
            }

            $params = $call[1] ?? [];
            $formatted[] = [
                'methodName' => $call[0]->value,
                'params' => $params,
            ];
        }

        return $this->rpc->multicall($formatted);
    }

    public function chat(string $message, ?string $login = null): void
    {
        if ($login === null) {
            $this->call(RpcMethod::CHAT_SEND_SERVER_MESSAGE, [$message]);
            return;
        }

        $this->call(
            RpcMethod::CHAT_SEND_SERVER_MESSAGE_TO_LOGIN,
            [$message, $login],
        );
    }

    public function render(
        string $template,
        array $context = [],
        ?string $login = null,
        int $timeout = 0,
        bool $hide = false
    ): void {
        $xml = $this->widgetBuilder->render($template, $context);

        if ($login === null) {
            $this->call(
                RpcMethod::SEND_DISPLAY_MANIALINK_PAGE,
                [$xml, $timeout, $hide],
            );
            return;
        }

        $this->call(
            RpcMethod::SEND_DISPLAY_MANIALINK_PAGE_TO_LOGIN,
            [$login, $xml, $timeout, $hide],
        );
    }

    public function closeWindow(int|array $ids): void
    {
        $ids = \is_array($ids) ? $ids : [$ids];
        $xml = "<manialinks>";
        foreach ($ids as $id) {
            $xml .= "<manialink id='{$id}'></manialink>";
        }
        $xml .= "</manialinks>";
        $this->call(RpcMethod::SEND_DISPLAY_MANIALINK_PAGE, [$xml]);
    }
}
