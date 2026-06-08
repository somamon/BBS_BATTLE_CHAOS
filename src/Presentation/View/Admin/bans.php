<?php
/**
 * BAN一覧。
 * @var array<int,array<string,mixed>> $bans
 * @var string|null $flash
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2>BAN（有効）</h2>

<?php if (!empty($flash)): ?>
  <div class="flash"><?= View::e($flash) ?></div>
<?php endif; ?>

<p class="muted">IP BAN の追加は「コンテンツ」画面で対象レスの「IP BAN」から行います。</p>

<?php if ($bans === []): ?>
  <p>有効なBANはありません。</p>
<?php else: ?>
<table>
  <thead><tr><th>種別</th><th>値</th><th>理由</th><th>登録</th><th>操作</th></tr></thead>
  <tbody>
  <?php foreach ($bans as $b): ?>
    <tr>
      <td><?= View::e($b['kind']) ?></td>
      <td style="font-family:monospace; font-size:12px;"><?= View::e($b['value']) ?></td>
      <td><?= View::e($b['reason'] ?? '') ?></td>
      <td><?= View::e($b['createdAt']) ?></td>
      <td><form method="post" action="/admin/bans/<?= View::e($b['id']) ?>/remove" style="margin:0;"><?= Csrf::field() ?><button class="btn">解除</button></form></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
