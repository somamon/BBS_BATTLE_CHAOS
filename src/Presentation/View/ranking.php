<?php
/**
 * 総資産ランキング。
 * @var array<int,array<string,mixed>> $rows  name,money,shareValue,total
 */
use App\Presentation\View\View;
?>
<h2>総資産ランキング</h2>

<?php if ($rows === []): ?>
  <div class="empty">まだランキングデータがありません。</div>
<?php else: ?>
  <div class="card">
    <table>
      <thead>
        <tr><th>順位</th><th>表示名</th><th>所持金</th><th>株評価額</th><th>総資産</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $r): ?>
          <tr>
            <td><?= View::e($i + 1) ?></td>
            <td><?= View::e($r['name']) ?></td>
            <td><?= View::e(number_format((int) $r['money'])) ?></td>
            <td><?= View::e(number_format((int) $r['shareValue'])) ?></td>
            <td style="color:#cc0000; font-weight:bold;"><?= View::e(number_format((int) $r['total'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
