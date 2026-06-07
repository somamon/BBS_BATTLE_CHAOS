<?php
/**
 * 終局結果 + 最終ランキング。
 * @var bool                            $over
 * @var string|null                     $reason
 * @var array<int,array<string,mixed>>  $rows  name,money,shareValue,total
 */
use App\Presentation\View\View;

$reasonLabels = [
    'all_dead' => '全てのスレッドが朽ち果てました',
    'no_money' => '市場の資金が尽きました',
];
?>
<?php if ($over): ?>
  <div class="banner">
    <h2>世界の終わり</h2>
    <p class="muted"><?= View::e($reasonLabels[$reason] ?? 'ゲーム終了') ?></p>
  </div>
<?php else: ?>
  <div class="card">
    <strong>ゲームは進行中です。</strong>
    <p class="muted">世界はまだ終わっていません。今のランキングはこちら。</p>
  </div>
<?php endif; ?>

<h2><?= $over ? '最終ランキング' : '現在のランキング' ?></h2>
<?php if ($rows === []): ?>
  <div class="empty">参加者がいません。</div>
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
            <td><?= View::e($r['name']) ?><?php if (!empty($r['isBot'])): ?> <span class="badge">AI</span><?php endif; ?></td>
            <td><?= View::e(number_format((int) $r['money'])) ?></td>
            <td><?= View::e(number_format((int) $r['shareValue'])) ?></td>
            <td style="color:#cc0000; font-weight:bold;"><?= View::e(number_format((int) $r['total'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
