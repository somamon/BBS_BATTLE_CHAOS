<?php

use App\Presentation\Controller\ThreadController;
use App\Presentation\Controller\PlaygroundController;
use App\Presentation\Routing\Router;

/** @var Router $router */

// 静的ルート
$router->get('/playground', [PlaygroundController::class, 'index']);
$router->get('/thread/create', [ThreadController::class, 'createForm']);

// 動的ルート（{id} を抽出）
$router->get('/thread/{id}', [ThreadController::class, 'show']);
$router->get('/thread/{id:\d+}/posts', [ThreadController::class, 'posts']); // 数字制約の例

// ミドルウェア付き（第3引数）
$router->post('/api/thread', [ThreadController::class, 'create'], ['csrf']);