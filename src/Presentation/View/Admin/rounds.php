<?php
/**
 * ラウンド管理。
 * @var int|null $current
 * @var int|null $ended
 * @var string|null $reason
 * @var array<int,array<string,mixed>> $rankings  前回ラウンドの確定ランキング
 * @var string|null $flash
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2>ラウンド</h2>

<?php if (!empty($flash)): ?>
  <div class="flash"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="cards">
  <div class="stat"><div class="n"><?= $current !== null ? '#' . View::e($current) : '-' ?></div><div class="l">進行中ラウンド</div></div>
  <div class="stat"><div class="n"><?= $ended !== null ? '#' . View::e($ended) : '-' ?></div><div class="l">直近の終局ラウンド（理由: <?= View::e($reason ?? '-') ?>）</div></div>
</div>

<div class="card" style="border:1px solid #e0a3a3; margin-top:14px;">
  <h3 style="margin-top:0; color:#b42318;">強制リセット</h3>
  <p class="muted">現在の順位を確定し、スレ・レス・株・所持金を初期化して新ラウンドを開始します。<strong>取り消せません。</strong></p>
  <form method="post" action="/admin/rounds/reset" data-confirm="本当にリセットしますか？取り消せません。">
    <?= Csrf::field() ?>
    <label for="password">確認のためパスワードを入力</label>
    <input type="password" id="password" name="password" required style="max-width:280px;">
    <button type="submit" class="btn danger" style="display:block; margin-top:8px;">リセットを実行</button>
  </form>
</div>

<?php if ($rankings !== []): ?>
<h3 style="margin-top:20px;">前回ラウンド #<?= View::e($ended) ?> の最終ランキング</h3>
<table>
  <thead><tr><th>順位</th><th>名前</th><th>所持金</th><th>株評価額</th><th>総資産</th></tr></thead>
  <tbody>
  <?php foreach ($rankings as $r): ?>
    <tr>
      <td><?= View::e($r['rank']) ?></td>
      <td><?= View::e($r['name']) ?></td>
      <td><?= View::e(number_format((int) $r['cash'])) ?></td>
      <td><?= View::e(number_format((int) $r['shareValue'])) ?></td>
      <td><?= View::e(number_format((int) $r['total'])) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
