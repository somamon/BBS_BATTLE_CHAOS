<?php
/**
 * 共通レイアウト。
 * @var string                              $title
 * @var string                              $content 本文HTML
 * @var string                              $phase   boom|calm|storm|crash
 * @var array{name:string,money:int}|null   $me      ログイン済みユーザー情報
 */
use App\Presentation\View\View;

$phase = $phase ?? 'calm';
$me    = $me ?? null;

$phaseColors = ['boom' => '#008800', 'calm' => '#444444', 'storm' => '#cc7000', 'crash' => '#cc0000'];
$phaseColor  = $phaseColors[$phase] ?? '#444444';
$phaseLabel  = isset($phaseColors[$phase]) ? t('phase.' . $phase) : t('phase.unknown');

$locale    = current_locale();
$otherLang = $locale === 'ja' ? 'en' : 'ja';

// 下部タブバーのアクティブ判定用に現在パスを取り出す。
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isTab = static function (string ...$prefixes) use ($path): bool {
    foreach ($prefixes as $p) {
        if ($path === $p || str_starts_with($path, $p . '/')) {
            return true;
        }
    }
    return false;
};
?>
<!DOCTYPE html>
<html lang="<?= View::e($locale) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= View::e($title ?? 'BBS') ?></title>
<style>
  body {
    font-family: "MS PGothic","ＭＳ Ｐゴシック","Hiragino Kaku Gothic ProN","ヒラギノ角ゴ ProN W3",Meiryo,sans-serif;
    margin: 0; background: #efefef; color: #000; font-size: 13px; line-height: 1.5;
  }
  a { color: #0000ee; text-decoration: underline; }
  a:visited { color: #660099; }
  a:hover { color: #ff0000; }
  header {
    background: #e0e0e0; border-bottom: 2px solid #889; padding: 5px 10px;
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 12px;
  }
  header h1 { font-size: 15px; margin: 0; font-weight: bold; }
  header h1 a { color: #cc0000; text-decoration: none; }
  header h1 a:hover { text-decoration: underline; }
  header nav { display: flex; gap: 10px; font-size: 12px; }
  .spacer { flex: 1; }
  .phase-badge { font-size: 12px; font-weight: bold; padding: 1px 6px; border: 1px solid currentColor; }
  .money { font-size: 12px; color: #cc0000; font-weight: bold; }
  .wrap { max-width: 900px; margin: 0 auto; padding: 10px; }
  .card {
    background: #f0e0d6; border: 1px solid #d9bfb7; border-radius: 0;
    padding: 8px 10px; margin-bottom: 6px;
  }
  .muted { color: #555; font-size: 11px; }
  input[type=text], input[type=email], input[type=password], input[type=number], textarea {
    box-sizing: border-box; background: #fff; color: #000;
    border: 1px solid #999; border-radius: 0; padding: 3px 5px; font-size: 13px;
  }
  input[type=text], input[type=email], input[type=password], textarea { width: 100%; }
  input[type=number] { width: 120px; }
  textarea { resize: vertical; min-height: 80px; }
  label { display: block; font-size: 12px; margin: 8px 0 3px; color: #333; }
  button {
    background: #f0f0f0; color: #000; border: 2px outset #f5f5f5; border-radius: 0;
    padding: 2px 12px; font-size: 13px; cursor: pointer; margin-top: 6px;
    font-family: inherit;
  }
  button:active { border-style: inset; }
  h2 { color: #cc0000; font-size: 16px; margin: 10px 0 6px; }
  h3 { color: #008800; font-size: 14px; margin: 12px 0 4px; }
  .empty { color: #888; padding: 18px 0; text-align: center; }
  .error { background: #ffe0e0; border: 1px solid #cc0000; color: #cc0000; border-radius: 0; padding: 6px 8px; margin-bottom: 8px; font-size: 13px; }
  .flash { background: #e0ffe0; border: 1px solid #008800; color: #006600; border-radius: 0; padding: 6px 8px; margin-bottom: 8px; font-size: 13px; }
  .hpbar { height: 10px; width: 220px; background: #ddd; border: 1px solid #999; border-radius: 0; overflow: hidden; margin: 4px 0; }
  .hpbar > span { display: block; height: 100%; background: #cc6666; }
  .badge { display: inline-block; font-size: 11px; font-weight: bold; padding: 0 4px; border-radius: 0; background: #fffbe0; border: 1px solid #cc0000; color: #cc0000; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; }
  th, td { text-align: left; padding: 3px 8px; border: 1px solid #ccc; }
  th { background: #e0e0e0; color: #000; font-weight: bold; }
  /* 列の多い表をSPで横スクロールさせる入れ物 */
  .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .banner { background: #fff; border: 2px solid #cc0000; border-radius: 0; padding: 16px; text-align: center; margin-bottom: 12px; }
  .banner h2 { margin: 0 0 4px; }
  /* 2chスタイルのレス表示 */
  .resnum { color: #cc0000; font-weight: bold; }
  .resname { color: #008800; font-weight: bold; }
  .resbody { margin: 4px 0 4px 1.5em; white-space: pre-wrap; }

  /* スマホ専用パーツ（PCでは隠す） */
  .tabbar { display: none; }
  .fab { display: none; }

  /* ===== スマホ対応（〜600px）：アプリ風レイアウト ===== */
  @media (max-width: 600px) {
    body {
      font-size: 15px; background: #f2f2f5;
      padding-bottom: calc(60px + env(safe-area-inset-bottom)); /* 下部タブバーの分 */
    }
    .wrap { padding: 10px; }

    /* 上部はアプリバー風：細く・固定・1段 */
    header {
      position: sticky; top: 0; z-index: 50;
      gap: 8px; padding: 8px 12px; flex-wrap: nowrap;
      box-shadow: 0 1px 3px rgba(0,0,0,.12);
    }
    header h1 { font-size: 16px; width: auto; }
    header nav { display: none; }          /* ナビは下部タブバーへ集約 */
    .spacer { display: block; flex: 1; }
    header .muted, header .money { font-size: 11px; }
    header .phase-badge { font-size: 11px; }

    /* カードをモダンに：角丸・余白・薄い影 */
    .card {
      border-radius: 12px; border: 1px solid #e6ddd8;
      box-shadow: 0 1px 2px rgba(0,0,0,.06);
      padding: 14px; margin-bottom: 12px;
    }
    .banner { border-radius: 12px; padding: 18px; }

    /* 入力はiOSのズーム防止に16px。フォームは指で押しやすく */
    input[type=text], input[type=email], input[type=password],
    input[type=number], textarea, select { font-size: 16px; padding: 10px; }
    label { font-size: 13px; }
    button {
      font-size: 16px; padding: 11px 18px; border-radius: 10px;
      border: 1px solid #c9b8b0; background: #fff;
    }
    button:active { background: #f0e0d6; }
    /* カード内フォームの送信ボタンは全幅でアプリ風に */
    .card > form > button[type=submit] { width: 100%; margin-top: 10px; }
    input[type=number] { width: 100%; max-width: 140px; }

    /* 列の多い表は横スクロール */
    .table-wrap table { white-space: nowrap; }

    h2 { font-size: 19px; }
    h3 { font-size: 15px; }
    .hpbar { width: 100%; height: 8px; border-radius: 4px; }
    .hpbar > span { border-radius: 4px; }

    /* ---- 下部タブバー ---- */
    .tabbar {
      display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 100;
      background: #fff; border-top: 1px solid #ddd;
      padding-bottom: env(safe-area-inset-bottom);
      box-shadow: 0 -1px 4px rgba(0,0,0,.08);
    }
    .tabbar a {
      flex: 1; text-align: center; padding: 7px 0 6px;
      color: #888; text-decoration: none; font-size: 10px; line-height: 1.3;
    }
    .tabbar a .ico { display: block; font-size: 20px; }
    .tabbar a.on { color: #cc0000; }
    .tabbar a:active { background: #f4f4f6; }

    /* ---- フローティングアクションボタン ---- */
    .fab {
      display: flex; align-items: center; justify-content: center;
      position: fixed; right: 16px; bottom: calc(68px + env(safe-area-inset-bottom));
      width: 56px; height: 56px; border-radius: 50%; z-index: 90;
      background: #cc0000; color: #fff; font-size: 30px; line-height: 1;
      text-decoration: none; box-shadow: 0 3px 8px rgba(0,0,0,.3);
    }
    .fab:visited, .fab:hover { color: #fff; }
  }
</style>
</head>
<body>
  <header>
    <h1><a href="/">BBS BATTLE CHAOS</a></h1>
    <nav>
      <a href="/"><?= View::e(t('nav.overview')) ?></a>
      <a href="/threads"><?= View::e(t('nav.threads')) ?></a>
      <a href="/ranking"><?= View::e(t('nav.ranking')) ?></a>
      <a href="/result"><?= View::e(t('nav.result')) ?></a>
      <?php if ($me !== null): ?><a href="/me"><?= View::e(t('nav.mypage')) ?></a><?php endif; ?>
    </nav>
    <span class="spacer"></span>
    <a href="/lang/<?= View::e($otherLang) ?>" class="phase-badge" style="color:#555; text-decoration:none;"><?= View::e(t('lang.other')) ?></a>
    <span class="phase-badge" style="color: <?= View::e($phaseColor) ?>">
      <?= View::e(t('header.market')) ?>: <?= View::e($phaseLabel) ?>
    </span>
    <?php if ($me !== null): ?>
      <span class="money"><?= View::e(t('header.cash')) ?> <?= View::e(number_format($me['money'])) ?></span>
      <span class="muted"><?= View::e($me['name']) ?></span>
      <form method="post" action="/logout" style="margin:0; display:inline;">
        <?= \App\Presentation\Http\Csrf::field() ?>
        <button type="submit" style="margin:0; font-size:11px;"><?= View::e(t('auth.logout')) ?></button>
      </form>
    <?php else: ?>
      <span class="muted"><a href="/register"><?= View::e(t('auth.register')) ?></a> / <a href="/login"><?= View::e(t('auth.login')) ?></a></span>
    <?php endif; ?>
  </header>
  <div class="wrap">
<?php $announcement = \App\Infrastructure\Runtime\SiteState::announcement(); ?>
<?php if ($announcement !== null): ?>
    <div class="card" style="background:#fffbe0; border-color:#cc0000;">📢 <?= View::e($announcement) ?></div>
<?php endif; ?>
<?= $content ?>
  </div>
  <footer style="border-top:1px solid #ccc; margin-top:16px; padding:10px; text-align:center; font-size:11px; color:#666;">
    <a href="/terms"><?= View::e(t('footer.terms')) ?></a>
    ｜ <a href="/privacy"><?= View::e(t('footer.privacy')) ?></a>
    ｜ <a href="/contact"><?= View::e(t('footer.contact')) ?></a>
    <div class="muted" style="margin-top:4px;"><?= View::e(t('footer.disclaimer')) ?></div>
  </footer>

  <!-- スマホ用 下部タブバー（PCでは非表示） -->
  <nav class="tabbar">
    <a href="/threads" class="<?= $isTab('/threads', '/thread') ? 'on' : '' ?>">
      <span class="ico">🧵</span><span class="lbl"><?= View::e(t('nav.threads')) ?></span>
    </a>
    <a href="/ranking" class="<?= $isTab('/ranking') ? 'on' : '' ?>">
      <span class="ico">🏆</span><span class="lbl"><?= View::e(t('nav.ranking')) ?></span>
    </a>
    <a href="/result" class="<?= $isTab('/result') ? 'on' : '' ?>">
      <span class="ico">🏁</span><span class="lbl"><?= View::e(t('nav.result')) ?></span>
    </a>
    <?php if ($me !== null): ?>
      <a href="/me" class="<?= $isTab('/me') ? 'on' : '' ?>">
        <span class="ico">👤</span><span class="lbl"><?= View::e(t('nav.mypage')) ?></span>
      </a>
    <?php else: ?>
      <a href="/login" class="<?= $isTab('/login', '/register') ? 'on' : '' ?>">
        <span class="ico">👤</span><span class="lbl"><?= View::e(t('auth.login')) ?></span>
      </a>
    <?php endif; ?>
  </nav>
</body>
</html>
