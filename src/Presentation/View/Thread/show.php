<?php
/**
 * スレッド（板）詳細＋投稿一覧。各投稿が投資対象（株価・レベル・投資ボタン）。
 * @var array<string,mixed>              $thread
 * @var array<int,array<string,mixed>>  $posts
 * @var bool                            $isLogin
 * @var string|null                     $flash
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;

$maxHp    = max(1, (int) $thread['maxHp']);
$pct      = max(0, min(100, (int) round((int) $thread['hp'] / $maxHp * 100)));
$writable = !empty($thread['writable']);
?>
<p><a href="/threads"><?= t('common.back_to_threads') ?></a></p>

<?php if (!empty($flash)): ?>
  <div class="flash"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card">
  <h2 style="margin:0 0 6px;"><?= View::e($thread['title']) ?></h2>
  <div class="hpbar"><span style="width: <?= $pct ?>%"></span></div>
  <div class="muted">
    <?= t('show.board_hp') ?> <?= View::e($thread['hp']) ?>/<?= View::e($thread['maxHp']) ?>
    ・ <?= t('threads.replies', ['n' => (int) $thread['postCount']]) ?>
    ・ <?= View::e(t('status.' . $thread['status'])) ?>
    ・ <?= View::e($thread['createdAt']) ?>
  </div>
  <?php if ($writable): ?>
    <div class="muted" style="margin-top:4px;"><?= t('show.invest_hint') ?></div>
  <?php else: ?>
    <div class="error" style="margin-top:4px;"><?= t('show.archived') ?></div>
  <?php endif; ?>
</div>

<h3><?= t('show.replies_heading', ['n' => count($posts)]) ?></h3>
<?php if ($posts === []): ?>
  <div class="empty"><?= t('show.no_replies') ?></div>
<?php else: ?>
  <?php foreach ($posts as $i => $p): ?>
    <?php
      $pMaxHp = max(1, (int) $p['maxHp']);
      $pPct   = max(0, min(100, (int) round((int) $p['hp'] / $pMaxHp * 100)));
    ?>
    <div class="card">
      <div>
        <span class="resnum"><?= View::e($i + 1) ?></span>
        ：<span class="resname"><?= t('show.name_anon') ?></span>
        ：<?= View::e($p['createdAt']) ?>
        ID:<?= View::e(substr((string) $p['authorHash'], 0, 8)) ?>
        <?php if ((int) $p['level'] > 0): ?>
          <span class="badge"><?= View::e(t('level.' . (int) $p['level'])) ?></span>
        <?php endif; ?>
        <?php if (!empty($p['dead'])): ?>
          <span class="badge" style="color:#777; border-color:#aaa;"><?= View::e(t('status.dead')) ?></span>
        <?php endif; ?>
      </div>
      <div class="resbody"><?= View::e($p['content']) ?></div>
      <div class="hpbar"><span style="width: <?= $pPct ?>%"></span></div>
      <div class="muted">
        HP <?= View::e($p['hp']) ?>/<?= View::e($p['maxHp']) ?>
        ・ <?= t('show.price') ?> ¥<?= View::e(number_format((float) $p['price'], 2)) ?>
        ・ <?= t('show.total_invested') ?> <?= View::e(number_format((int) $p['totalInvested'])) ?>
        ・ <?= t('show.total_shares') ?> <?= View::e(number_format((int) $p['totalShares'])) ?>
        <?php if ((int) $p['myShares'] > 0): ?>
          ｜ <span style="color:#cc0000;"><?= t('show.holding', ['shares' => number_format((int) $p['myShares']), 'val' => number_format((int) $p['myValuation'])]) ?></span>
        <?php endif; ?>
      </div>
      <?php if ($isLogin && $writable && empty($p['dead'])): ?>
        <form method="post" action="/post/<?= View::e($p['id']) ?>/invest" style="margin-top:6px;">
          <?= Csrf::field() ?>
          <input type="hidden" name="thread_id" value="<?= View::e($thread['id']) ?>">
          <input type="number" name="amount" min="1" value="100" style="width:90px;">
          <button type="submit"><?= t('show.invest_btn') ?></button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php if (!$isLogin && $writable): ?>
  <p class="muted"><?= t('show.login_to_invest_pre') ?> <a href="/login"><?= t('auth.login') ?></a> <?= t('show.login_to_invest_post') ?></p>
<?php endif; ?>

<?php if ($writable): ?>
  <div class="card">
    <strong><?= t('show.write_reply') ?></strong>
    <form method="post" action="/thread/<?= View::e($thread['id']) ?>/posts">
      <?= Csrf::field() ?>
      <label for="content"><?= t('show.content_label') ?></label>
      <textarea id="content" name="content" maxlength="2000" placeholder="<?= View::e(t('show.content_placeholder')) ?>" required></textarea>
      <button type="submit"><?= t('show.submit_reply') ?></button>
    </form>
  </div>
<?php else: ?>
  <div class="card muted"><?= t('show.archived_footer') ?></div>
<?php endif; ?>
