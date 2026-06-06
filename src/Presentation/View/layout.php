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
    'boom'  => ['ブーム相場', '#4ade80'],
    'calm'  => ['平穏相場',   '#9385ff'],
    'storm' => ['荒れ相場',   '#fbbf24'],
    'crash' => ['暴落相場',   '#f87171'],
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
  body { font-family: -apple-system, sans-serif; margin: 0; background: #1e1e2e; color: #e4e4f0; }
  a { color: #9385ff; text-decoration: none; }
  a:hover { text-decoration: underline; }
  header { padding: 12px 16px; border-bottom: 1px solid #3b3d57; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
  header h1 { font-size: 16px; margin: 0; }
  header nav { display: flex; gap: 14px; font-size: 13px; }
  .spacer { flex: 1; }
  .phase-badge { font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; border: 1px solid currentColor; }
  .money { font-size: 13px; color: #ffd479; font-weight: 600; }
  .wrap { max-width: 720px; margin: 0 auto; padding: 16px; }
  .card { background: #15151f; border: 1px solid #3b3d57; border-radius: 8px; padding: 14px 16px; margin-bottom: 10px; }
  .muted { color: #9a9ab5; font-size: 12px; }
  input[type=text], input[type=email], input[type=password], input[type=number], textarea {
    width: 100%; box-sizing: border-box; background: #15151f; color: #e4e4f0;
    border: 1px solid #3b3d57; border-radius: 6px; padding: 10px; font-size: 14px;
  }
  textarea { resize: vertical; min-height: 80px; }
  label { display: block; font-size: 13px; margin: 10px 0 4px; color: #c9c9e0; }
  button {
    background: #7c6cff; color: #fff; border: 0; border-radius: 6px;
    padding: 8px 18px; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 8px;
  }
  button:hover { background: #9385ff; }
  .empty { color: #9a9ab5; padding: 24px 0; text-align: center; }
  .error { background: #3a1f24; border: 1px solid #f87171; color: #fca5a5; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; font-size: 13px; }
  .flash { background: #1f3a2a; border: 1px solid #4ade80; color: #86efac; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; font-size: 13px; }
  .hpbar { height: 8px; background: #2a2a3d; border-radius: 4px; overflow: hidden; margin: 6px 0; }
  .hpbar > span { display: block; height: 100%; background: linear-gradient(90deg, #f87171, #4ade80); }
  .badge { display: inline-block; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: #2a2a3d; color: #c4b5fd; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #2a2a3d; }
  th { color: #9a9ab5; font-weight: 600; }
  .banner { background: #2a1a3d; border: 1px solid #9385ff; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 16px; }
  .banner h2 { margin: 0 0 6px; }
</style>
</head>
<body>
  <header>
    <h1><a href="/threads">⚔ BBS BATTLE CHAOS</a></h1>
    <nav>
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
        <button type="submit" style="padding:4px 10px; margin:0; font-size:12px; background:#3b3d57;">ログアウト</button>
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
