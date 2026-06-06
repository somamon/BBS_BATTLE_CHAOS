<?php

namespace App\Presentation\Controller;

use App\Presentation\View\Thread\index;

class ThreadController
{
    public function index(): mixed
    {
        // 掲示板を全て取得する
        return index;
    }
}
