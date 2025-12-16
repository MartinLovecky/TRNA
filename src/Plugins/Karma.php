<?php

declare(strict_types=1);

namespace Yuha\Trna\Plugins;

use Yuha\Trna\Plugins\Karma\Auth;

class Karma
{
    public function __construct(private Auth $auth)
    {
    }

    public function auth()
    {
        $this->auth->authenticate();
    }
}
