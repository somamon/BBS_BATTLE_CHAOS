<?php
/**
 * スレッド一覧。
 * @var array<int, array<string, mixed>> $threads
 *   各要素 id,title,hp,maxHp,mutationLevel,totalShares,postCount,createdAt
 */
use App\Presentation\View\View;
?>
<p><a href="/thread/create">＋ 新しいスレッドを立てる</a> ・ <a href="/threads/dead">🪦 墓場（朽ちたスレ）</a></p>

<?php if ($threads === []): ?>
  <div class="empty">まだ生存しているスレッドがありません。</div>
<?php else: ?>
  <?php foreach ($threads as $t): ?>
    <?php
      $maxHp = max(1, (int) $t['maxHp']);
      $pct   = max(0, min(100, (int) round((int) $t['hp'] / $maxHp * 100)));
    ?>
    <div class="card">
      <div>
        <a href="/thread/<?= View::e($t['id']) ?>"><?= View::e($t['title']) ?></a>
        <?php if ((int) $t['mutationLevel'] > 0): ?>
          <span class="badge">変異Lv<?= View::e($t['mutationLevel']) ?></span>
        <?php endif; ?>
      </div>
      <div class="hpbar"><span style="width: <?= $pct ?>%"></span></div>
      <div class="muted">
        HP <?= View::e($t['hp']) ?>/<?= View::e($t['maxHp']) ?>
        ・ 勢い <?= View::e($t['postCount']) ?>レス
        ・ 時価総額 <?= View::e(number_format((int) $t['hp'])) ?>
        ・ 総株数 <?= View::e($t['totalShares']) ?>
        ・ <?= View::e($t['createdAt']) ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
