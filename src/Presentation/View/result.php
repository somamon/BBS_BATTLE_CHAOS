<?php
/**
 * 終局結果 + 最終ランキング。
 * @var bool                            $over
 * @var string|null                     $reason
 * @var array<int,array<string,mixed>>  $rows  name,isBot,money,shareValue,total
 * @var int|null                        $roundNo  進行中ラウンド番号
 */
use App\Presentation\View\View;

$roundNo = $roundNo ?? null;

$reasonKey = match ($reason) {
    'all_dead' => 'result.reason.all_dead',
    'no_money' => 'result.reason.no_money',
    default    => 'result.reason.over',
};
?>
<?php if ($roundNo !== null): ?>
  <p class="muted"><?= t('result.round', ['n' => $roundNo]) ?></p>
<?php endif; ?>

<?php if ($over): ?>
  <div class="banner">
    <h2><?= t('result.world_end') ?></h2>
    <p class="muted"><?= t($reasonKey) ?></p>
    <p class="muted"><?= t('result.reset_note') ?></p>
  </div>
<?php else: ?>
  <div class="card">
    <strong><?= t('result.ongoing_title') ?></strong>
    <p class="muted"><?= t('result.ongoing_note') ?></p>
  </div>
<?php endif; ?>

<h2><?= $over ? t('result.final_ranking') : t('result.current_ranking') ?></h2>
<?php if ($rows === []): ?>
  <div class="empty"><?= t('result.no_players') ?></div>
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
