<?php

use App\Presentation\Controller\AuthController;
use App\Presentation\Controller\HomeController;
use App\Presentation\Controller\InvestController;
use App\Presentation\Controller\LanguageController;
use App\Presentation\Controller\MyPageController;
use App\Presentation\Controller\RankingController;
use App\Presentation\Controller\ResController;
use App\Presentation\Controller\ResultController;
use App\Presentation\Controller\ThreadController;
use App\Presentation\Routing\Router;

/** @var Router $router */

// トップ（サイト概要・遊び方）
$router->get('/', [HomeController::class, 'index']);

// 言語切替（Cookieに保存して元のページへ）
$router->get('/lang/{lang}', [LanguageController::class, 'switch']);

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
$router->get('/verify', [AuthController::class, 'verify']);                          // メール確認リンク
$router->get('/verify/resend', [AuthController::class, 'resendForm']);               // 再送フォーム
$router->post('/verify/resend', [AuthController::class, 'resend'], ['csrf']);        // 再送実行

// マイページ・ランキング・結果
$router->get('/me', [MyPageController::class, 'index'], ['auth']);
$router->get('/ranking', [RankingController::class, 'index']);
$router->get('/result', [ResultController::class, 'index']);
