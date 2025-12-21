<?php

declare(strict_types=1);

namespace Yuha\Trna\Service;

use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Infrastructure\Xml\Request;
use Yuha\Trna\Infrastructure\Xml\Response;

class DediClient
{
    private const string ENDPOINT = 'http://dedimania.net:8002/Dedimania';

    public function __construct(
        private HttpClient $httpClient,
        private Request $request,
        private Response $response
    ) {
    }

    public function request(string $type, array $params): array|TmContainer
    {
        $xml = $this->request->createMultiCallRequest($params);
        $res = $this->httpClient->postXml(self::ENDPOINT, $xml);

        if (!$res) {
            return ['ok' => false, 'reason' => 'response_failed'];
        }

        return $this->response->processResponse($type, $res, true);
    }

    public function authenticate(): array
    {
        return [
            'methodName' => 'dedimania.Authenticate',
            'params' => [
                [
                    'Game'     => 'TMF',
                    'Login'    => $_ENV['dedi_username'],
                    'Password' => $_ENV['dedi_code'],
                    'Tool'     => 'Xaseco',
                    'Version'  => '1.16',
                    'Nation'   => 'CZE',
                    'Packmask' => 'Stadium',
                ],
            ],
        ];
    }

    public function validateAccount(): array
    {
        return [
            'methodName' => 'dedimania.ValidateAccount',
            'params' => [],
        ];
    }

    public function playerArrive(TmContainer $player): array
    {
        return [
            'methodName' => 'dedimania.PlayerArrive',
            'params' => [
                'Game' => 'TMF',
                'Login' => $player->get('Login'),
                'Nation' => $player->get('Nation'),
                'Nickname' => $player->get('NickName'),
                'TeamName' => $player->get('LadderStats.TeamName'),
                'LadderRanking' => $player->get('LadderStats.PlayerRankings.0.Ranking'),
                'IsSpectator' => $player->get('IsSpectator'),
                'IsOfficial' => $player->get('IsInOfficialMode'),
            ],
        ];
    }

    public function warningsAndTTR(): array
    {
        return [
            'methodName' => 'dedimania.WarningsAndTTR',
            'params' => [],
        ];
    }
}
