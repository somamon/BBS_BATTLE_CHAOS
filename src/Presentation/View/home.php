<?php
/**
 * トップページ（サイト概要・遊び方）。
 * @var bool $isLogin
 */
use App\Config\Game;
use App\Presentation\View\View;

$tiers = Game::POST_LEVEL_TIERS; // [100, 1000, 10000]
?>
<div class="banner">
  <h2>BBS BATTLE CHAOS</h2>
  <p class="muted">面白い投稿に“お金を賭ける”＝株を買う。早く見抜いた目利きが儲かる匿名掲示板バトル。</p>
</div>

<div class="card">
  <h3 style="margin-top:0;">どんなサイト？</h3>
  <p>
    ここは普通の掲示板ではありません。誰でも匿名でスレ立て・レスができ、
    登録ユーザーは「面白い」と思ったレスに<strong>投資して株を買えます</strong>。
  </p>
  <p>
    後から投資が集まるほどそのレスの<strong>株価が上がる</strong>ので、
    まだ誰も気づいていない名レスを<strong>早く見抜いて仕込んだ人ほど資産が増える</strong>——
    「目利き」が主役のゲームです。
  </p>
</div>

<div class="card">
  <h3 style="margin-top:0;">2つの層</h3>
  <table>
    <tr>
      <th>匿名掲示板層（誰でも）</th>
      <td>匿名でスレ立て・レス。お金も登録も不要。スレもレスも時間で<strong>朽ちて消える</strong>。</td>
    </tr>
    <tr>
      <th>投資家層（要登録）</th>
      <td>初期資金 <strong><?= View::e(number_format(Game::INITIAL_MONEY)) ?></strong> でレスの株を買う。後から買う人が増えるほど株価が上がり、早く仕込んだ株が値上がりする。</td>
    </tr>
  </table>
</div>

<div class="card">
  <h3 style="margin-top:0;">遊び方（中核ループ）</h3>
  <ol>
    <li>匿名でレスを書く（無料・無報酬）</li>
    <li>面白いレスに<strong>お金を賭ける＝株を買う</strong>（要登録）</li>
    <li>後続の投資が増えるほど<strong>株価が上がる</strong></li>
    <li>早く買った株が値上がりして<strong>資産が増える</strong></li>
    <li>累計投資でレスが進化（<span class="badge">新規</span> → <span class="badge">注目</span> → <span class="badge">人気</span> → <span class="badge">殿堂入り</span>）</li>
    <li>終局時の<strong>総資産（所持金＋保有株の評価額）でランキング</strong></li>
  </ol>
  <p class="muted">勝つのは、良いレスを“早く”見抜いた目利き。</p>
</div>

<div class="card">
  <h3 style="margin-top:0;">押さえておくルール</h3>
  <ul>
    <li><strong>早い者勝ち</strong>：株価は累計投資額で上がる（ボンディングカーブ）。後から買うほど割高。</li>
    <li><strong>朽ちる</strong>：スレもレスもHPを持ち、放置すると時間で減って消滅（dead）。dead の株は紙くず。</li>
    <li><strong>相場の天候</strong>：世界フェーズ（ブーム/平穏/荒れ/暴落）でHPの減りやすさが変わる。今の相場はヘッダーで確認できます。</li>
    <li><strong>レベルで延命</strong>：投資が集まったレスは耐久(max HP)が上がり、名作ほど長生きする。</li>
    <li><strong>含み損益</strong>：保有株の評価額は株価×鮮度(HP)。朽ちかけを買うと即含み損になることも。</li>
  </ul>
</div>

<div class="card" style="text-align:center;">
  <h3 style="margin-top:0;">はじめる</h3>
  <?php if ($isLogin): ?>
    <p>[ <a href="/threads">スレ一覧を見る</a> ] [ <a href="/me">マイページ</a> ] [ <a href="/ranking">ランキング</a> ]</p>
  <?php else: ?>
    <p>[ <a href="/threads">まずは覗いてみる（匿名OK）</a> ]</p>
    <p>[ <a href="/register">新規登録して投資する</a> ] [ <a href="/login">ログイン</a> ]</p>
  <?php endif; ?>
</div>
