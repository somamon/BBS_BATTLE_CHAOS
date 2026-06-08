<?php
/**
 * 監査ログ。
 * @var array<int,array<string,mixed>> $logs
 */
use App\Presentation\View\View;
?>
<h2>監査ログ（最近200件）</h2>

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
