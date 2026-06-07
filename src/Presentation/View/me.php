<?php
/**
 * マイページ。保有株は投稿(post)単位（doc21 §5）。
 * @var int                             $money
 * @var int                             $shareValue
 * @var int                             $total
 * @var array<int,array<string,mixed>>  $holdings  postId,threadId,excerpt,shares,price,valuation,cost,pnl,level,status,postHp
 */
use App\Presentation\View\View;
?>
<h2>マイページ</h2>

<div class="card">
  <div>所持金 <strong><?= View::e(number_format($money)) ?></strong></div>
  <div>株評価額 <strong><?= View::e(number_format($shareValue)) ?></strong></div>
  <div>総資産 <strong style="color:#cc0000;"><?= View::e(number_format($total)) ?></strong></div>
</div>

<h3>保有株（投稿単位）</h3>
<?php if ($holdings === []): ?>
  <div class="empty">まだ株を保有していません。気になるレスに投資してみよう。</div>
<?php else: ?>
  <div class="card">
    <table>
      <thead>
        <tr><th>投稿</th><th>Lv</th><th>株数</th><th>株価</th><th>評価額</th><th>含み損益</th><th>状態</th></tr>
      </thead>
      <tbody>
        <?php foreach ($holdings as $h): ?>
          <?php $pnl = (int) $h['pnl']; ?>
          <tr>
            <td><a href="/thread/<?= View::e($h['threadId']) ?>"><?= View::e($h['excerpt']) ?></a></td>
            <td><?= View::e($h['level']) ?></td>
            <td><?= View::e(number_format((int) $h['shares'])) ?></td>
            <td>¥<?= View::e(number_format((float) $h['price'], 2)) ?></td>
            <td><?= View::e(number_format((int) $h['valuation'])) ?></td>
            <td style="color:<?= $pnl >= 0 ? '#008800' : '#cc0000' ?>;">
              <?= $pnl >= 0 ? '+' : '' ?><?= View::e(number_format($pnl)) ?>
            </td>
            <td><?= View::e($h['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
