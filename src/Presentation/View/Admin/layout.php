<?php
/**
 * 管理画面レイアウト（公開側とは別系統・簡素なUI）。
 * @var string $title
 * @var string $active   ナビのアクティブキー（dashboard|users）
 * @var string $content
 */
use App\Presentation\View\View;

$nav = [
    'dashboard' => ['/admin', 'ダッシュボード'],
    'content'   => ['/admin/content', 'コンテンツ'],
    'reports'   => ['/admin/reports', '通報'],
    'users'     => ['/admin/users', 'ユーザー'],
    'bans'      => ['/admin/bans', 'BAN'],
    'contact'   => ['/admin/contact', 'お問い合わせ'],
    'rounds'    => ['/admin/rounds', 'ラウンド'],
    'settings'  => ['/admin/settings', '設定'],
    'audit'     => ['/admin/audit', '監査ログ'],
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= View::e($title) ?> - 管理</title>
<style>
  body { font-family: system-ui, sans-serif; margin: 0; background: #f5f6f8; color: #1a1a1a; font-size: 14px; }
  header { background: #1f2430; color: #fff; padding: 10px 16px; display: flex; align-items: center; gap: 16px; }
  header .brand { font-weight: bold; }
  header nav { display: flex; gap: 14px; }
  header nav a { color: #cdd3df; text-decoration: none; padding: 4px 2px; }
  header nav a.on { color: #fff; border-bottom: 2px solid #4f8cff; }
  header .spacer { flex: 1; }
  header form { margin: 0; }
  header button { background: transparent; color: #cdd3df; border: 1px solid #444b5a; border-radius: 6px; padding: 4px 10px; cursor: pointer; }
  .wrap { max-width: 960px; margin: 0 auto; padding: 16px; }
  .cards { display: flex; flex-wrap: wrap; gap: 12px; }
  .stat { background: #fff; border: 1px solid #e3e6ec; border-radius: 10px; padding: 14px 18px; min-width: 130px; flex: 1; }
  .stat .n { font-size: 26px; font-weight: bold; }
  .stat .l { color: #666; font-size: 12px; margin-top: 4px; }
  table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; }
  th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #eef0f4; font-size: 13px; }
  th { background: #f0f2f6; }
  .badge { display: inline-block; font-size: 11px; padding: 1px 8px; border-radius: 999px; }
  .badge.active { background: #e3f6e9; color: #1a7f3c; }
  .badge.suspended { background: #fde6e6; color: #b42318; }
  .badge.admin { background: #e6efff; color: #1d4ed8; }
  .btn { display: inline-block; border: 1px solid #c9ced8; background: #fff; border-radius: 6px; padding: 4px 10px; font-size: 12px; cursor: pointer; }
  .btn.danger { border-color: #e0a3a3; color: #b42318; }
  h2 { font-size: 18px; }
  .flash { background: #e3f6e9; border: 1px solid #1a7f3c; color: #166534; padding: 8px 12px; border-radius: 8px; margin-bottom: 12px; }
</style>
</head>
<body>
  <header>
    <span class="brand">BBS BATTLE CHAOS 管理</span>
    <?php
      $badges = [
          'reports' => \App\Infrastructure\Runtime\AdminBadges::reports(),
          'contact' => \App\Infrastructure\Runtime\AdminBadges::contact(),
      ];
    ?>
    <nav>
      <?php foreach ($nav as $key => [$href, $label]): ?>
        <a href="<?= View::e($href) ?>" class="<?= $active === $key ? 'on' : '' ?>"><?= View::e($label) ?><?php if (!empty($badges[$key])): ?> <span style="background:#cc0000; color:#fff; border-radius:999px; padding:0 6px; font-size:11px;"><?= View::e($badges[$key]) ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </nav>
    <span class="spacer"></span>
    <a href="/" style="color:#cdd3df; text-decoration:none;">サイトへ →</a>
    <form method="post" action="/logout">
      <?= \App\Presentation\Http\Csrf::field() ?>
      <button type="submit">ログアウト</button>
    </form>
  </header>
  <div class="wrap">
<?= $content ?>
  </div>
</body>
</html>
