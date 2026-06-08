<?php
/**
 * コンテンツモデレーション。
 * @var array<int,array<string,mixed>> $threads
 * @var array<int,array<string,mixed>> $posts
 * @var string|null $flash
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;

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

<h3>スレッド（最近50件）</h3>
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

<h3 style="margin-top:20px;">レス（最近50件）</h3>
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
        <form method="post" action="/admin/posts/<?= View::e($p['id']) ?>/ban" style="margin:0;" onsubmit="return confirm('この投稿者のIPをBANしますか？');"><?= Csrf::field() ?><button class="btn danger">IP BAN</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
