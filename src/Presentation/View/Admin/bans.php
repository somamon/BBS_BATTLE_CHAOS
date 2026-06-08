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

<div class="card">
  <h3 style="margin-top:0;">IPを直接BAN</h3>
  <form method="post" action="/admin/bans" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
    <?= Csrf::field() ?>
    <input type="text" name="ip" placeholder="IPアドレス（例 203.0.113.10）" style="width:220px;">
    <input type="text" name="reason" placeholder="理由（任意）" style="width:160px;">
    <select name="days"><option value="0">無期限</option><option value="1">1日</option><option value="7">7日</option><option value="30">30日</option></select>
    <button type="submit" class="btn danger">BAN</button>
  </form>
  <p class="muted" style="font-size:11px;">レス単位のBANは「コンテンツ」画面の「IP BAN」からも行えます。</p>
</div>

<h3>有効なBAN</h3>
<?php if ($bans === []): ?>
  <p>有効なBANはありません。</p>
<?php else: ?>
<table>
  <thead><tr><th>種別</th><th>値</th><th>理由</th><th>期限</th><th>登録</th><th>操作</th></tr></thead>
  <tbody>
  <?php foreach ($bans as $b): ?>
    <tr>
      <td><?= View::e($b['kind']) ?></td>
      <td style="font-family:monospace; font-size:12px;"><?= View::e($b['value']) ?></td>
      <td><?= View::e($b['reason'] ?? '') ?></td>
      <td><?= View::e($b['expiresAt']) ?></td>
      <td><?= View::e($b['createdAt']) ?></td>
      <td><form method="post" action="/admin/bans/<?= View::e($b['id']) ?>/remove" style="margin:0;"><?= Csrf::field() ?><button class="btn">解除</button></form></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
