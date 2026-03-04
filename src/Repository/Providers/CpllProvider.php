<?php

declare(strict_types=1);

use Yuha\Trna\Core\Enums\Window;
use Yuha\Trna\Core\Window\WindowDataProvider;
use Yuha\Trna\Repository\Players;
use Yuha\Trna\Service\Aseco;

final class CpllProvider implements WindowDataProvider
{
    public function __construct(
        private array &$cpll,
        private Players $players,
    ) {
    }

    public function getData(
        ?string $login = null,
        ?Window $window = null,
        ?array $context = null
    ): array {
        $isMyCp = $context['isMyCp'] ?? false;

        if ($isMyCp && !isset($this->cpll[$login])) {
            return [];
        }

        uasort($this->cpll, static fn ($a, $b) => $a['cp'] <=> $b['cp']);

        $rows = [];

        $mycp = $this->cpll[$login]['cp'] ?? null;

        foreach ($this->cpll as $playerLogin => $val) {

            if ($isMyCp && $mycp !== $val['cp']) {
                continue;
            }

            $player = $this->players->getByLogin($playerLogin);

            $rows[] = [
                $val['cp'],
                Aseco::getFormattedTime($val['time']),
                $player->get('NickName'),
            ];
        }

        return $rows;
    }
}
