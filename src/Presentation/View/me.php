<?php
/**
 * マイページ。
 * @var int                             $money
 * @var int                             $shareValue
 * @var int                             $total
 * @var array<int,array<string,mixed>>  $holdings  threadId,threadTitle,shares,valuation,status,threadHp
 */
use App\Presentation\View\View;
?>
<h2>マイページ</h2>

<div class="card">
  <div>所持金 <strong><?= View::e(number_format($money)) ?></strong></div>
  <div>株評価額 <strong><?= View::e(number_format($shareValue)) ?></strong></div>
  <div>総資産 <strong style="color:#ffd479;"><?= View::e(number_format($total)) ?></strong></div>
</div>

<h3>保有株</h3>
<?php if ($holdings === []): ?>
  <div class="empty">まだ株を保有していません。気になる板に投資してみよう。</div>
<?php else: ?>
  <div class="card">
    <table>
      <thead>
        <tr><th>スレッド</th><th>株数</th><th>評価額</th><th>状態</th><th>スレHP</th></tr>
      </thead>
      <tbody>
        <?php foreach ($holdings as $h): ?>
          <tr>
            <td><a href="/thread/<?= View::e($h['threadId']) ?>"><?= View::e($h['threadTitle']) ?></a></td>
            <td><?= View::e($h['shares']) ?></td>
            <td><?= View::e(number_format((int) $h['valuation'])) ?></td>
            <td><?= View::e($h['status']) ?></td>
            <td><?= View::e($h['threadHp']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
