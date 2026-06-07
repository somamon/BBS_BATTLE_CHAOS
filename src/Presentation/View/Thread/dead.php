<?php

/**
 * 墓場。朽ちて消滅したスレッドをタイトルのみ表示する（閲覧専用・復活不可）。
 * @var array<int, array<string, mixed>> $threads
 */

use App\Presentation\View\View;
?>
<p><a href="/threads"><?= t('common.back_to_threads') ?></a></p>

<h2><?= t('dead.title') ?></h2>

<?php if ($threads === []): ?>
  <div class="empty"><?= t('dead.empty') ?></div>
<?php else: ?>
  <p class="muted"><?= t('dead.view_hint') ?></p>
  <?php foreach ($threads as $t): ?>
    <div class="card">
      <a class="tombstone" href="/thread/<?= View::e($t['id']) ?>"><?= View::e($t['title']) ?></a>
      <div class="muted"><?= t('dead.died', ['from' => View::e($t['createdAt']), 'to' => View::e($t['diedAt'])]) ?></div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<style>
  .tombstone {
    color: #777;
    font-weight: bold;
    text-decoration: line-through;
    text-decoration-color: #aaa;
  }
</style>
