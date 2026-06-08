<?php

use App\Presentation\Controller\Admin;
use App\Presentation\Controller\AccountController;
use App\Presentation\Controller\AuthController;
use App\Presentation\Controller\ContactController;
use App\Presentation\Controller\GoogleAuthController;
use App\Presentation\Controller\HomeController;
use App\Presentation\Controller\InvestController;
use App\Presentation\Controller\LanguageController;
use App\Presentation\Controller\LegalController;
use App\Presentation\Controller\MyPageController;
use App\Presentation\Controller\ReportController;
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
$router->post('/post/{id}/sell', [InvestController::class, 'sell'], ['csrf', 'auth']);     // 株の売却（要ログイン）

// 認証
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register'], ['csrf']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login'], ['csrf']);
$router->post('/logout', [AuthController::class, 'logout'], ['csrf']);
$router->get('/verify', [AuthController::class, 'verify']);                          // メール確認リンク
$router->get('/verify/resend', [AuthController::class, 'resendForm']);               // 再送フォーム
$router->post('/verify/resend', [AuthController::class, 'resend'], ['csrf']);        // 再送実行

// Googleアカウントログイン（OAuth/OIDC）
$router->get('/auth/google', [GoogleAuthController::class, 'start']);                 // 同意画面へ
$router->get('/auth/google/callback', [GoogleAuthController::class, 'callback']);     // コールバック

// パスワード再設定（M1）
$router->get('/password/forgot', [AuthController::class, 'forgotForm']);             // 申請フォーム
$router->post('/password/forgot', [AuthController::class, 'forgot'], ['csrf']);      // 申請（メール送信）
$router->get('/password/reset', [AuthController::class, 'resetForm']);               // 新パスワード設定フォーム
$router->post('/password/reset', [AuthController::class, 'reset'], ['csrf']);        // 新パスワード確定

// マイページ・ランキング・結果
$router->get('/me', [MyPageController::class, 'index'], ['auth']);
$router->get('/ranking', [RankingController::class, 'index']);
$router->get('/result', [ResultController::class, 'index']);

// 退会（アカウント削除。要ログイン。M5）
$router->get('/account/delete', [AccountController::class, 'confirm'], ['auth']);
$router->post('/account/delete', [AccountController::class, 'delete'], ['csrf', 'auth']);

// 法務ページ（M5）
$router->get('/terms', [LegalController::class, 'terms']);
$router->get('/privacy', [LegalController::class, 'privacy']);

// お問い合わせ
$router->get('/contact', [ContactController::class, 'form']);
$router->post('/contact', [ContactController::class, 'submit'], ['csrf']);

// 通報（公開側）
$router->get('/report', [ReportController::class, 'form']);
$router->post('/report', [ReportController::class, 'submit'], ['csrf']);

// 管理画面（admin ミドルウェアで role=admin に限定。変更系は csrf 併用）
$router->get('/admin', [Admin\DashboardController::class, 'index'], ['admin']);
$router->get('/admin/users', [Admin\UserController::class, 'index'], ['admin']);
$router->post('/admin/users/{id}/suspend', [Admin\UserController::class, 'suspend'], ['csrf', 'admin']);
$router->post('/admin/users/{id}/unsuspend', [Admin\UserController::class, 'unsuspend'], ['csrf', 'admin']);
$router->post('/admin/users/{id}/ban', [Admin\UserController::class, 'ban'], ['csrf', 'admin']);
$router->post('/admin/users/{id}/unban', [Admin\UserController::class, 'unban'], ['csrf', 'admin']);
// コンテンツモデレーション（非表示・復帰）
$router->get('/admin/content', [Admin\ContentController::class, 'index'], ['admin']);
$router->post('/admin/threads/{id}/hide', [Admin\ContentController::class, 'hideThread'], ['csrf', 'admin']);
$router->post('/admin/threads/{id}/unhide', [Admin\ContentController::class, 'unhideThread'], ['csrf', 'admin']);
$router->post('/admin/posts/{id}/hide', [Admin\ContentController::class, 'hidePost'], ['csrf', 'admin']);
$router->post('/admin/posts/{id}/unhide', [Admin\ContentController::class, 'unhidePost'], ['csrf', 'admin']);
// 通報対応
$router->get('/admin/reports', [Admin\ReportController::class, 'index'], ['admin']);
$router->post('/admin/reports/{id}/resolve', [Admin\ReportController::class, 'resolve'], ['csrf', 'admin']);
$router->post('/admin/reports/{id}/reject', [Admin\ReportController::class, 'reject'], ['csrf', 'admin']);
// BAN
$router->post('/admin/posts/{id}/ban', [Admin\ContentController::class, 'banPost'], ['csrf', 'admin']);
$router->get('/admin/bans', [Admin\BanController::class, 'index'], ['admin']);
$router->post('/admin/bans', [Admin\BanController::class, 'add'], ['csrf', 'admin']);
$router->post('/admin/bans/{id}/remove', [Admin\BanController::class, 'remove'], ['csrf', 'admin']);
// お問い合わせ
$router->get('/admin/contact', [Admin\ContactController::class, 'index'], ['admin']);
$router->post('/admin/contact/{id}/done', [Admin\ContactController::class, 'done'], ['csrf', 'admin']);
// ラウンド管理（強制リセットは再認証つき）
$router->get('/admin/rounds', [Admin\RoundController::class, 'index'], ['admin']);
$router->post('/admin/rounds/reset', [Admin\RoundController::class, 'reset'], ['csrf', 'admin']);
// 設定（バランス・メンテ・アナウンス）
$router->get('/admin/settings', [Admin\SettingController::class, 'index'], ['admin']);
$router->post('/admin/settings', [Admin\SettingController::class, 'update'], ['csrf', 'admin']);
// 監査ログ
$router->get('/admin/audit', [Admin\AuditController::class, 'index'], ['admin']);
