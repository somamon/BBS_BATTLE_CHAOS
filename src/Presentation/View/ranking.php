<?php
/**
 * 総資産ランキング。
 * @var array<int,array<string,mixed>> $rows  name,isBot,money,shareValue,total
 */
use App\Presentation\View\View;
?>
<h2><?= t('ranking.title') ?></h2>

<?php if ($rows === []): ?>
  <div class="empty"><?= t('ranking.empty') ?></div>
<?php else: ?>
  <div class="card">
    <table>
      <thead>
        <tr><th><?= t('ranking.rank') ?></th><th><?= t('ranking.name') ?></th><th><?= t('ranking.cash') ?></th><th><?= t('ranking.shares') ?></th><th><?= t('ranking.total') ?></th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $r): ?>
          <tr>
            <td><?= View::e($i + 1) ?></td>
            <td><?= View::e($r['name']) ?><?php if (!empty($r['isBot'])): ?> <span class="badge">NPC</span><?php endif; ?></td>
            <td><?= View::e(number_format((int) $r['money'])) ?></td>
            <td><?= View::e(number_format((int) $r['shareValue'])) ?></td>
            <td style="color:#cc0000; font-weight:bold;"><?= View::e(number_format((int) $r['total'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
