<?php
/**
 * 通報管理。
 * @var array<int,array<string,mixed>> $reports
 * @var string|null $flash
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2>通報（未対応）</h2>

<?php if (!empty($flash)): ?>
  <div class="flash"><?= View::e($flash) ?></div>
<?php endif; ?>

<?php if ($reports === []): ?>
  <p>未対応の通報はありません。</p>
<?php else: ?>
<table>
  <thead><tr><th>対象</th><th>理由</th><th>詳細</th><th>日時</th><th>操作</th></tr></thead>
  <tbody>
  <?php foreach ($reports as $r): ?>
    <tr>
      <td>
        <?= View::e($r['targetType']) ?>
        <?php if ($r['link'] !== null): ?>
          <a href="<?= View::e($r['link']) ?>" target="_blank">開く</a>
        <?php else: ?>
          <span class="muted" style="font-size:11px;"><?= View::e(mb_substr((string) $r['targetId'], 0, 12)) ?>…</span>
        <?php endif; ?>
      </td>
      <td><?= View::e($r['reason']) ?></td>
      <td style="max-width:280px;"><?= View::e($r['detail'] ?? '') ?></td>
      <td><?= View::e($r['createdAt']) ?></td>
      <td style="display:flex; gap:6px;">
        <form method="post" action="/admin/reports/<?= View::e($r['id']) ?>/resolve" style="margin:0;"><?= Csrf::field() ?><button class="btn">対応済み</button></form>
        <form method="post" action="/admin/reports/<?= View::e($r['id']) ?>/reject" style="margin:0;"><?= Csrf::field() ?><button class="btn">却下</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
