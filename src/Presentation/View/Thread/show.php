<?php
/**
 * スレッド（板）詳細＋投稿一覧。各投稿が投資対象（株価・レベル・投資ボタン）。
 * @var array<string,mixed>              $thread  id,title,hp,maxHp,postCount,status,createdAt
 * @var array<int,array<string,mixed>>  $posts   id,authorHash,content,hp,maxHp,level,levelLabel,price,totalInvested,totalShares,myShares,myValuation,createdAt
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
  <h2 style="margin:0 0 6px;"><?= View::e($thread['title']) ?></h2>
  <div class="hpbar"><span style="width: <?= $pct ?>%"></span></div>
  <div class="muted">
    板HP <?= View::e($thread['hp']) ?>/<?= View::e($thread['maxHp']) ?>
    ・ <?= View::e($thread['postCount']) ?>レス
    ・ <?= View::e($thread['status']) ?>
    ・ <?= View::e($thread['createdAt']) ?>
  </div>
  <div class="muted" style="margin-top:4px;">面白いレスに賭けて株を買おう。早く買うほど株価が安い。</div>
</div>

<h3>レス（<?= View::e(count($posts)) ?>）</h3>
<?php if ($posts === []): ?>
  <div class="empty">まだレスがありません。最初の1レスを書こう。</div>
<?php else: ?>
  <?php foreach ($posts as $i => $p): ?>
    <?php
      $pMaxHp = max(1, (int) $p['maxHp']);
      $pPct   = max(0, min(100, (int) round((int) $p['hp'] / $pMaxHp * 100)));
    ?>
    <div class="card">
      <div>
        <span class="resnum"><?= View::e($i + 1) ?></span>
        ：<span class="resname">名無しさん</span>
        ：<?= View::e($p['createdAt']) ?>
        ID:<?= View::e(substr((string) $p['authorHash'], 0, 8)) ?>
        <?php if ((int) $p['level'] > 0): ?>
          <span class="badge"><?= View::e($p['levelLabel']) ?></span>
        <?php endif; ?>
      </div>
      <div class="resbody"><?= View::e($p['content']) ?></div>
      <div class="hpbar"><span style="width: <?= $pPct ?>%"></span></div>
      <div class="muted">
        HP <?= View::e($p['hp']) ?>/<?= View::e($p['maxHp']) ?>
        ・ 株価 ¥<?= View::e(number_format((float) $p['price'], 2)) ?>
        ・ 累計投資 <?= View::e(number_format((int) $p['totalInvested'])) ?>
        ・ 総株数 <?= View::e(number_format((int) $p['totalShares'])) ?>
        <?php if ((int) $p['myShares'] > 0): ?>
          ｜ <span style="color:#cc0000;">保有 <?= View::e(number_format((int) $p['myShares'])) ?>株（評価額 <?= View::e(number_format((int) $p['myValuation'])) ?>）</span>
        <?php endif; ?>
      </div>
      <?php if ($isLogin): ?>
        <form method="post" action="/post/<?= View::e($p['id']) ?>/invest" style="margin-top:6px;">
          <?= Csrf::field() ?>
          <input type="hidden" name="thread_id" value="<?= View::e($thread['id']) ?>">
          <input type="number" name="amount" min="1" value="100" style="width:90px;">
          <button type="submit">このレスに投資</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php if (!$isLogin): ?>
  <p class="muted">投資するには <a href="/login">ログイン</a> してください（レスは匿名で書けます）。</p>
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
