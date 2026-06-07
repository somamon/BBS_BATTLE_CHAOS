<?php
/**
 * トップページ（サイト概要・遊び方）。
 * @var bool $isLogin
 */
use App\Config\Game;
use App\Presentation\View\View;
?>
<div class="banner">
  <h2>BBS BATTLE CHAOS</h2>
  <p class="muted"><?= t('home.tagline') ?></p>
</div>

<div class="card">
  <h3 style="margin-top:0;"><?= t('home.what.title') ?></h3>
  <p><?= t('home.what.p1') ?></p>
  <p><?= t('home.what.p2') ?></p>
</div>

<div class="card">
  <h3 style="margin-top:0;"><?= t('home.layers.title') ?></h3>
  <table>
    <tr>
      <th><?= t('home.layers.anon.head') ?></th>
      <td><?= t('home.layers.anon.body') ?></td>
    </tr>
    <tr>
      <th><?= t('home.layers.investor.head') ?></th>
      <td><?= t('home.layers.investor.body', ['money' => number_format(Game::INITIAL_MONEY)]) ?></td>
    </tr>
  </table>
</div>

<div class="card">
  <h3 style="margin-top:0;"><?= t('home.loop.title') ?></h3>
  <ol>
    <li><?= t('home.loop.s1') ?></li>
    <li><?= t('home.loop.s2') ?></li>
    <li><?= t('home.loop.s3') ?></li>
    <li><?= t('home.loop.s4') ?></li>
    <li><?= t('home.loop.s5') ?></li>
    <li><?= t('home.loop.s6') ?></li>
  </ol>
  <p class="muted"><?= t('home.loop.note') ?></p>
</div>

<div class="card">
  <h3 style="margin-top:0;"><?= t('home.rules.title') ?></h3>
  <ul>
    <li><?= t('home.rules.r1') ?></li>
    <li><?= t('home.rules.r2') ?></li>
    <li><?= t('home.rules.r3') ?></li>
    <li><?= t('home.rules.r4') ?></li>
    <li><?= t('home.rules.r5') ?></li>
  </ul>
</div>

<div class="card">
  <h3 style="margin-top:0;"><?= t('home.npc.title') ?></h3>
  <p><?= t('home.npc.p1') ?></p>
  <ul>
    <li><?= t('home.npc.l1') ?></li>
    <li><?= t('home.npc.l2', ['limit' => Game::BOT_MAX_HUMANS]) ?></li>
    <li><?= t('home.npc.l3') ?></li>
  </ul>
  <p class="muted"><?= t('home.npc.note') ?></p>
</div>

<div class="card" style="text-align:center;">
  <h3 style="margin-top:0;"><?= t('home.start.title') ?></h3>
  <?php if ($isLogin): ?>
    <p>[ <a href="/threads"><?= t('home.start.threads') ?></a> ] [ <a href="/me"><?= t('nav.mypage') ?></a> ] [ <a href="/ranking"><?= t('nav.ranking') ?></a> ]</p>
  <?php else: ?>
    <p>[ <a href="/threads"><?= t('home.start.peek') ?></a> ]</p>
    <p>[ <a href="/register"><?= t('home.start.register') ?></a> ] [ <a href="/login"><?= t('auth.login') ?></a> ]</p>
  <?php endif; ?>
</div>
