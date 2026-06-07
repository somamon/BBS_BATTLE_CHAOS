<?php
/**
 * 確認メール送信完了。
 * @var string $email
 */
use App\Presentation\View\View;
?>
<h2>確認メールを送信しました</h2>

<div class="card">
  <p><strong><?= View::e($email) ?></strong> 宛に確認メールを送信しました。</p>
  <p class="muted">メール内のリンク（24時間有効）を開くと登録が完了し、そのままログインします。</p>
  <p class="muted">リンクを開くまではログインできません。</p>
</div>

<p class="muted">メールが届かない場合は <a href="/verify/resend">確認メールを再送</a> できます。</p>
<p><a href="/login">ログインへ</a></p>
