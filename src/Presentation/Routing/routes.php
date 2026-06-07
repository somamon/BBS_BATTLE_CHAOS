<?php

use App\Presentation\Controller\AuthController;
use App\Presentation\Controller\InvestController;
use App\Presentation\Controller\MyPageController;
use App\Presentation\Controller\PlaygroundController;
use App\Presentation\Controller\RankingController;
use App\Presentation\Controller\ResController;
use App\Presentation\Controller\ResultController;
use App\Presentation\Controller\ThreadController;
use App\Presentation\Routing\Router;

/** @var Router $router */

// 開発用 JS プレイグラウンド
$router->get('/playground', [PlaygroundController::class, 'index']);

// スレッド一覧・作成
$router->get('/threads', [ThreadController::class, 'index']);            // 一覧
$router->get('/threads/dead', [ThreadController::class, 'dead']);        // 墓場（朽ちたスレのタイトル一覧）
$router->get('/thread/create', [ThreadController::class, 'createForm']); // 作成フォーム
$router->post('/threads', [ThreadController::class, 'create'], ['csrf']); // スレ作成

// スレッド詳細（{id} は ULID なので制約なし）
$router->get('/thread/{id}', [ThreadController::class, 'show']);

// 書き込み系（CSRF 検証付き）
$router->post('/thread/{id}/posts', [ResController::class, 'create'], ['csrf']);          // レス投稿
$router->post('/post/{id}/invest', [InvestController::class, 'invest'], ['csrf', 'auth']); // 投稿へ投資（要ログイン）

// 認証
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register'], ['csrf']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login'], ['csrf']);
$router->post('/logout', [AuthController::class, 'logout'], ['csrf']);

// マイページ・ランキング・結果
$router->get('/me', [MyPageController::class, 'index'], ['auth']);
$router->get('/ranking', [RankingController::class, 'index']);
$router->get('/result', [ResultController::class, 'index']);
