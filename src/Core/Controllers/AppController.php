<?php

declare(strict_types=1);

namespace Yuha\Trna\Core\Controllers;

use Yuha\Trna\Repository\Challange;

class AppController
{
    public function __construct(
        private Challange $challange,
    ) {
    }

    public function run()
    {
        dd($this->challange);
    }
}
