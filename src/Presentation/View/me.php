<?php
/**
 * マイページ。保有株は投稿(post)単位。
 * @var int                             $money
 * @var int                             $shareValue
 * @var int                             $total
 * @var array<int,array<string,mixed>>  $holdings  postId,threadId,excerpt,shares,price,valuation,cost,pnl,level,status,postHp
 */
use App\Presentation\View\View;
?>
<h2><?= t('me.title') ?></h2>

<?php if (!empty($flash)): ?><div class="flash"><?= View::e($flash) ?></div><?php endif; ?>

<div class="card">
  <div><?= t('me.cash') ?> <strong><?= View::e(number_format($money)) ?></strong></div>
  <div><?= t('me.share_value') ?> <strong><?= View::e(number_format($shareValue)) ?></strong></div>
  <div><?= t('me.total') ?> <strong style="color:#cc0000;"><?= View::e(number_format($total)) ?></strong></div>
</div>

<div class="card">
  <form method="post" action="/me/name">
    <?= \App\Presentation\Http\Csrf::field() ?>
    <label for="name"><?= t('me.name_label') ?></label>
    <input type="text" id="name" name="name" value="<?= View::e($name) ?>" maxlength="50" required>
    <div class="muted" style="margin-top:4px;"><?= t('me.name_hint') ?></div>
    <button type="submit"><?= t('me.name_change') ?></button>
  </form>
</div>

<h3><?= t('me.holdings_title') ?></h3>
<?php if ($holdings === []): ?>
  <div class="empty"><?= t('me.empty') ?></div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap">
    <table>
      <thead>
        <tr><th><?= t('me.col_post') ?></th><th><?= t('me.col_lv') ?></th><th><?= t('me.col_shares') ?></th><th><?= t('me.col_price') ?></th><th><?= t('me.col_value') ?></th><th><?= t('me.col_pnl') ?></th><th><?= t('me.col_status') ?></th></tr>
      </thead>
      <tbody>
        <?php foreach ($holdings as $h): ?>
          <?php $pnl = (int) $h['pnl']; ?>
          <tr>
            <td><a href="/thread/<?= View::e($h['threadId']) ?>"><?= View::e($h['excerpt']) ?></a></td>
            <td><?= View::e(t('level.' . (int) $h['level'])) ?></td>
            <td><?= View::e(number_format((int) $h['shares'])) ?></td>
            <td>¥<?= View::e(number_format((float) $h['price'], 2)) ?></td>
            <td><?= View::e(number_format((int) $h['valuation'])) ?></td>
            <td style="color:<?= $pnl >= 0 ? '#008800' : '#cc0000' ?>;">
              <?= $pnl >= 0 ? '+' : '' ?><?= View::e(number_format($pnl)) ?>
            </td>
            <td><?= View::e(t('status.' . $h['status'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
<?php endif; ?>

<p class="muted" style="margin-top:16px;">
  <a href="/account/delete"><?= t('account.delete.link') ?></a>
</p>
