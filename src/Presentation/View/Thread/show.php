<?php
/**
 * スレッド詳細。
 * @var array<string,mixed>              $thread  id,title,hp,maxHp,mutationLevel,totalShares,postCount,status,createdAt
 * @var array<int,array<string,mixed>>  $posts   id,authorHash,authorId,content,hp,createdAt
 * @var bool                            $isLogin
 * @var string|null                     $flash
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;

$maxHp = max(1, (int) $thread['maxHp']);
$pct   = max(0, min(100, (int) round((int) $thread['hp'] / $maxHp * 100)));
?>
<p><a href="/threads">← スレ一覧へ</a></p>

<?php if (!empty($flash)): ?>
  <div class="flash"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card">
  <h2 style="margin:0 0 6px;">
    <?= View::e($thread['title']) ?>
    <?php if ((int) $thread['mutationLevel'] > 0): ?>
      <span class="badge">変異Lv<?= View::e($thread['mutationLevel']) ?></span>
    <?php endif; ?>
  </h2>
  <div class="hpbar"><span style="width: <?= $pct ?>%"></span></div>
  <div class="muted">
    HP <?= View::e($thread['hp']) ?>/<?= View::e($thread['maxHp']) ?>
    ・ 総株数 <?= View::e($thread['totalShares']) ?>
    ・ 勢い <?= View::e($thread['postCount']) ?>レス
    ・ <?= View::e($thread['status']) ?>
    ・ <?= View::e($thread['createdAt']) ?>
  </div>
</div>

<div class="card">
  <strong>この板に投資</strong>
  <?php if ($isLogin): ?>
    <form method="post" action="/thread/<?= View::e($thread['id']) ?>/invest">
      <?= Csrf::field() ?>
      <label for="amount">投資額</label>
      <input type="number" id="amount" name="amount" min="1" value="100">
      <button type="submit">この板に投資する</button>
    </form>
  <?php else: ?>
    <p class="muted">投資するには <a href="/login">ログイン</a> してください。</p>
  <?php endif; ?>
</div>

<h3>レス（<?= View::e(count($posts)) ?>）</h3>
<?php if ($posts === []): ?>
  <div class="empty">まだレスがありません。最初の1レスを書こう。</div>
<?php else: ?>
  <?php foreach ($posts as $i => $p): ?>
    <div class="card">
      <div class="muted">
        #<?= View::e($i + 1) ?>
        ・ ID:<?= View::e(substr((string) $p['authorHash'], 0, 8)) ?>
        ・ HP <?= View::e($p['hp']) ?>
        ・ <?= View::e($p['createdAt']) ?>
      </div>
      <div style="white-space: pre-wrap; margin-top: 6px;"><?= View::e($p['content']) ?></div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<div class="card">
  <strong>レスを書く</strong>
  <form method="post" action="/thread/<?= View::e($thread['id']) ?>/posts">
    <?= Csrf::field() ?>
    <label for="content">本文</label>
    <textarea id="content" name="content" maxlength="2000" placeholder="本文を入力" required></textarea>
    <button type="submit">書き込む</button>
  </form>
</div>
