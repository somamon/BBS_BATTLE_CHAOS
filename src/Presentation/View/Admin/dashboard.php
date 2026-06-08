<?php
/**
 * 管理ダッシュボード。
 * @var array{humans:int,aliveThreads:int,alivePosts:int,investments:int,round:?int} $stats
 */
use App\Presentation\View\View;
?>
<h2>ダッシュボード</h2>
<div class="cards">
  <div class="stat"><div class="n"><?= View::e(number_format($stats['humans'])) ?></div><div class="l">登録ユーザー（人間）</div></div>
  <div class="stat"><div class="n"><?= View::e(number_format($stats['aliveThreads'])) ?></div><div class="l">生存スレッド</div></div>
  <div class="stat"><div class="n"><?= View::e(number_format($stats['alivePosts'])) ?></div><div class="l">生存レス</div></div>
  <div class="stat"><div class="n"><?= View::e(number_format($stats['investments'])) ?></div><div class="l">投資（累計）</div></div>
  <div class="stat"><div class="n"><?= $stats['round'] !== null ? '#' . View::e($stats['round']) : '-' ?></div><div class="l">現ラウンド</div></div>
</div>
<p class="l" style="color:#888; margin-top:16px; font-size:12px;">※件数は概算（HP減衰は表示時に確定するため）。詳細なKPIは構造化ログを参照。</p>
