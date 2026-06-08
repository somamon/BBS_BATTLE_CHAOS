<?php
/**
 * コンテンツモデレーション。
 * @var array<int,array<string,mixed>> $threads
 * @var array<int,array<string,mixed>> $posts
 * @var int $tp @var int $tPages @var int $pp @var int $pPages
 * @var string|null $flash
 */
use App\Presentation\View\View;
use App\Presentation\Http\Csrf;

/** 各セクションのページャ（相手側のページ番号を保持）。 */
$pager = static function (string $param, int $cur, int $pages, int $tp, int $pp): string {
    if ($pages <= 1) {
        return '';
    }
    $url = static fn (int $n): string => '/admin/content?tp=' . ($param === 'tp' ? $n : $tp) . '&pp=' . ($param === 'pp' ? $n : $pp);
    $h = '<div style="margin:8px 0;">';
    $h .= $cur > 1 ? '<a class="btn" href="' . View::e($url($cur - 1)) . '">← 前</a> ' : '';
    $h .= '<span class="muted">' . $cur . ' / ' . $pages . '</span>';
    $h .= $cur < $pages ? ' <a class="btn" href="' . View::e($url($cur + 1)) . '">次 →</a>' : '';
    return $h . '</div>';
};

$hideBtn = static function (string $action, string $id, bool $hidden): string {
    $verb = $hidden ? 'unhide' : 'hide';
    $label = $hidden ? '復帰' : '非表示';
    $cls = $hidden ? 'btn' : 'btn danger';
    $csrf = Csrf::field();
    return "<form method=\"post\" action=\"/admin/{$action}/" . View::e($id) . "/{$verb}\" style=\"margin:0;\">{$csrf}<button class=\"{$cls}\">{$label}</button></form>";
};
?>
<h2>コンテンツ</h2>

<?php if (!empty($flash)): ?>
  <div class="flash"><?= View::e($flash) ?></div>
<?php endif; ?>

<h3>スレッド</h3>
<?= $pager('tp', $tp, $tPages, $tp, $pp) ?>
<table>
  <thead><tr><th>タイトル</th><th>言語</th><th>状態</th><th>表示</th><th>操作</th></tr></thead>
  <tbody>
  <?php foreach ($threads as $t): ?>
    <tr style="<?= $t['hidden'] ? 'opacity:.55;' : '' ?>">
      <td><a href="/thread/<?= View::e($t['id']) ?>" target="_blank"><?= View::e($t['title']) ?></a></td>
      <td><?= View::e($t['lang']) ?></td>
      <td><?= View::e($t['status']) ?></td>
      <td><?= $t['hidden'] ? '<span class="badge suspended">非表示</span>' : '<span class="badge active">表示</span>' ?></td>
      <td><?= $hideBtn('threads', (string) $t['id'], (bool) $t['hidden']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?= $pager('tp', $tp, $tPages, $tp, $pp) ?>

<h3 style="margin-top:20px;">レス</h3>
<?= $pager('pp', $pp, $pPages, $tp, $pp) ?>
<table>
  <thead><tr><th>本文（抜粋）</th><th>状態</th><th>表示</th><th>操作</th></tr></thead>
  <tbody>
  <?php foreach ($posts as $p): ?>
    <tr style="<?= $p['hidden'] ? 'opacity:.55;' : '' ?>">
      <td><a href="/thread/<?= View::e($p['threadId']) ?>" target="_blank"><?= View::e($p['excerpt']) ?></a></td>
      <td><?= View::e($p['status']) ?></td>
      <td><?= $p['hidden'] ? '<span class="badge suspended">非表示</span>' : '<span class="badge active">表示</span>' ?></td>
      <td style="display:flex; gap:6px;">
        <?= $hideBtn('posts', (string) $p['id'], (bool) $p['hidden']) ?>
        <form method="post" action="/admin/posts/<?= View::e($p['id']) ?>/ban" style="margin:0; display:flex; gap:4px;" data-confirm="この投稿者のIPをBANしますか？"><?= Csrf::field() ?>
          <select name="days" style="font-size:11px;"><option value="0">無期限</option><option value="1">1日</option><option value="7">7日</option><option value="30">30日</option></select>
          <button class="btn danger">IP BAN</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?= $pager('pp', $pp, $pPages, $tp, $pp) ?>
