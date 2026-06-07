<?php
/**
 * スレッド（板）一覧。
 * @var array<int, array<string, mixed>> $threads
 *   各要素 id,title,hp,maxHp,postCount,createdAt
 */
use App\Presentation\View\View;
?>
<p>[ <a href="/thread/create">新しいスレッドを立てる</a> ] [ <a href="/threads/dead">墓場（朽ちたスレ）</a> ]</p>

<?php if ($threads === []): ?>
  <div class="empty">まだ生存しているスレッドがありません。</div>
<?php else: ?>
  <?php foreach ($threads as $i => $t): ?>
    <?php
      $maxHp = max(1, (int) $t['maxHp']);
      $pct   = max(0, min(100, (int) round((int) $t['hp'] / $maxHp * 100)));
    ?>
    <div class="card">
      <div>
        <span class="resnum"><?= View::e($i + 1) ?>:</span>
        <a href="/thread/<?= View::e($t['id']) ?>"><?= View::e($t['title']) ?></a>
        <span class="muted">(<?= View::e($t['postCount']) ?>)</span>
      </div>
      <div class="hpbar"><span style="width: <?= $pct ?>%"></span></div>
      <div class="muted">
        板HP <?= View::e($t['hp']) ?>/<?= View::e($t['maxHp']) ?>
        ・ <?= View::e($t['postCount']) ?>レス
        ・ <?= View::e($t['createdAt']) ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
