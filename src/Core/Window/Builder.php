<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Window;

use Yuha\Trna\Core\Enums\{Action, Window};
use Yuha\Trna\Infrastructure\Gbx\Client;

class Builder
{
    private const ROWS_PER_PAGE = 8;

    public function __construct(
        private readonly Client $client,
        private readonly Codec $codec,
        private readonly Registry $registry
    ) {
    }

    public function display(
        Window $window,
        ?string $login,
        array $data,
        string $header = 'help',
        bool $close = false
    ): void {
        $context = $this->build($window, $header, $data, $login);

        if (isset($login)) {
            $this->client->sendRenderToLogin(
                login: $login,
                template: $window->template(),
                context: $context,
                hide: $close,
            );
        } else {
            $this->client->sendRenderToAll(
                template: $window->template(),
                context: $context,
                hide: $close,
            );
        }
    }

    private function build(
        Window $window,
        string $header,
        array $rows,
        ?string $login,
    ): array {
        $totalPages = max(1, (int) ceil(\count($rows) / self::ROWS_PER_PAGE));
        $this->registry->register($window, $totalPages);

        $currentPage = $this->registry->current($login, $window);

        $pageRows = \array_slice(
            $rows,
            ($currentPage - 1) * self::ROWS_PER_PAGE,
            self::ROWS_PER_PAGE,
        );

        return [
            'id' => $window->value,
            'header' => $window->name . ' ' . $header,
            'data' => $pageRows,
            'current' => $currentPage,
            'total' => $totalPages,
            // Navigation buttons
            'first' => $this->codec->encode($window, Action::First),
            'prev' => $this->codec->encode($window, Action::Prev),
            'next' => $this->codec->encode($window, Action::Next),
            'last' => $this->codec->encode($window, Action::Last),
            // Choice buttons
            'yes' => $this->codec->encode($window, Action::Yes),
            'no' => $this->codec->encode($window, Action::No),
            'cancel' => $this->codec->encode($window, Action::Cancel),
            'pass' => $this->codec->encode($window, Action::Pass),
            // Close button
            'close' => $this->codec->encode($window, Action::Close),
        ];
    }
}
