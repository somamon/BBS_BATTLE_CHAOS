<?php
/**
 * 監査ログ。
 * @var array<int,array<string,mixed>> $logs
 * @var string $filterAdmin
 * @var string $filterAction
 */
use App\Presentation\View\View;
?>
<h2>監査ログ（最近200件）</h2>

<form method="get" action="/admin/audit" class="card" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
  <input type="text" name="admin" value="<?= View::e($filterAdmin ?? '') ?>" placeholder="管理者ID（完全一致）" style="width:200px;">
  <input type="text" name="action" value="<?= View::e($filterAction ?? '') ?>" placeholder="操作（部分一致 例: user.）" style="width:200px;">
  <button type="submit" class="btn">絞り込み</button>
  <?php if (!empty($filterAdmin) || !empty($filterAction)): ?><a href="/admin/audit" class="btn">クリア</a><?php endif; ?>
</form>

<?php if ($logs === []): ?>
  <p>記録はありません。</p>
<?php else: ?>
<table>
  <thead><tr><th>日時</th><th>管理者</th><th>操作</th><th>対象</th><th>詳細</th><th>IP</th></tr></thead>
  <tbody>
  <?php foreach ($logs as $l): ?>
    <tr>
      <td><?= View::e($l['createdAt']) ?></td>
      <td style="font-family:monospace; font-size:11px;"><?= View::e(mb_substr((string) $l['adminId'], 0, 12)) ?></td>
      <td><?= View::e($l['action']) ?></td>
      <td style="font-size:12px;"><?= View::e($l['targetType'] ?? '') ?> <?= View::e(mb_substr((string) ($l['targetId'] ?? ''), 0, 14)) ?></td>
      <td style="font-size:12px;"><?= View::e($l['detail'] ?? '') ?></td>
      <td style="font-size:11px;"><?= View::e($l['ip'] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
