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

$phaseLabels = [
    'boom'  => ['ブーム相場', '#008800'],
    'calm'  => ['平穏相場',   '#444444'],
    'storm' => ['荒れ相場',   '#cc7000'],
    'crash' => ['暴落相場',   '#cc0000'],
];
[$phaseLabel, $phaseColor] = $phaseLabels[$phase] ?? ['不明', '#9a9ab5'];
?>
<!DOCTYPE html>
<html lang="ja">
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
  .banner { background: #fff; border: 2px solid #cc0000; border-radius: 0; padding: 16px; text-align: center; margin-bottom: 12px; }
  .banner h2 { margin: 0 0 4px; }
  /* 2chスタイルのレス表示 */
  .resnum { color: #cc0000; font-weight: bold; }
  .resname { color: #008800; font-weight: bold; }
  .resbody { margin: 4px 0 4px 1.5em; white-space: pre-wrap; }
</style>
</head>
<body>
  <header>
    <h1><a href="/">BBS BATTLE CHAOS</a></h1>
    <nav>
      <a href="/">概要</a>
      <a href="/threads">スレ一覧</a>
      <a href="/ranking">ランキング</a>
      <a href="/result">結果</a>
      <?php if ($me !== null): ?><a href="/me">マイページ</a><?php endif; ?>
    </nav>
    <span class="spacer"></span>
    <span class="phase-badge" style="color: <?= View::e($phaseColor) ?>">
      相場: <?= View::e($phaseLabel) ?>
    </span>
    <?php if ($me !== null): ?>
      <span class="money">所持金 <?= View::e(number_format($me['money'])) ?></span>
      <span class="muted"><?= View::e($me['name']) ?></span>
      <form method="post" action="/logout" style="margin:0; display:inline;">
        <?= \App\Presentation\Http\Csrf::field() ?>
        <button type="submit" style="margin:0; font-size:11px;">ログアウト</button>
      </form>
    <?php else: ?>
      <span class="muted"><a href="/register">登録</a> / <a href="/login">ログイン</a></span>
    <?php endif; ?>
  </header>
  <div class="wrap">
<?= $content ?>
  </div>
</body>
</html>
