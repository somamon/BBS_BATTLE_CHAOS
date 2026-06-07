<?php

/**
 * 墓場。朽ちて消滅したスレッドをタイトルのみ表示する（閲覧専用・復活不可）。
 * @var array<int, array<string, mixed>> $threads
 *   各要素 id,title,createdAt,diedAt
 */

use App\Presentation\View\View;
?>
<p><a href="/threads">← スレッド一覧へ</a></p>

<h2>🪦 墓場</h2>

<?php if ($threads === []): ?>
  <div class="empty">まだ朽ちたスレッドはありません。</div>
<?php else: ?>
  <?php foreach ($threads as $t): ?>
    <div class="card">
      <div class="tombstone"><?= View::e($t['title']) ?></div>
      <div class="muted"><?= View::e($t['createdAt']) ?> 〜 <?= View::e($t['diedAt']) ?> に朽ちた</div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<style>
  .tombstone {
    color: #9a9ab5;
    font-weight: 600;
    text-decoration: line-through;
    text-decoration-color: #5a5a72;
  }
</style>