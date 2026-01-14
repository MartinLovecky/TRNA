<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins\Karma;

use Yuha\Trna\Core\TmContainer;
use Yuha\Trna\Core\Traits\LoggerAware;
use Yuha\Trna\Service\Aseco;
use Yuha\Trna\Service\HttpClient;

class Auth
{
    use LoggerAware;
    private string $api = '';

    public function __construct(private HttpClient $httpClient)
    {
        $this->initLog('Karma-Auth');
        $this->api = $_ENV['KarmaAPI'] ?? 'http://worldwide.mania-karma.com/api/tmforever-trackmania-v4.php';
    }

    public function authenticate()
    {
        if (!isset($_ENV['authcode'])) {
            $result = $this->performAuth();
        }
    }

    private function performAuth(): array
    {
        $userAgent = \sprintf(
            'XAseco/%s mania-karma/%s %s/%s php/%s %s/%s/%s',
            '1.16',
            '1.0',
            'TMF',
            '2011-02-21',
            PHP_VERSION,
            php_uname('s'),
            php_uname('r'),
            php_uname('m'),
        );

        $this->httpClient->setUserAgent($userAgent);
        $res = $this->httpClient->get(
            $this->api,
            [
                'Action' => 'Auth',
                'login'  => 'yuhzel',
                'name'   => 'TEST',
                'game'   => 'TMF',
                'zone'   => 'Czech republic|Ustecký kraj',
                'nation' => $_ENV['dedi_nation'],
            ],
        );

        if (!\is_string($res)) {
            return ['ok' => false, 'reason' => 'response_failed'];
        }

        $output = TmContainer::fromXMLString($res);
        $status = $output->get('status');

        if ($status !== 100 && $status !== 200) {
            return ['ok' => false, 'reason' => 'karma_error_' . $status];
        }

        return $this->authSuccess($output);
    }

    private function authSuccess(TmContainer $output): array
    {
        Aseco::updateEnvFile('KarmaAPI', $output->get('api_url'));
        Aseco::updateEnvFile('authcode', $output->get('authcode'));

        if (!$output->get('import_done')) {
            // TODO: IMPORT DATA
            return ['ok' => true];
        }

        return ['ok' => true];
    }
}
