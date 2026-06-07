<?php
/**
 * 再送完了（列挙防止のため、アカウントの有無に関わらず同じ文面）。
 * @var string $email
 */
use App\Presentation\View\View;
?>
<h2>確認メールを再送しました</h2>

<div class="card">
  <p><strong><?= View::e($email) ?></strong> が未確認のアカウントとして登録されている場合、確認メールを再送しました。</p>
  <p class="muted">メール内のリンク（24時間有効）を開くと登録が完了します。古いリンクは無効になります。</p>
</div>

<p class="muted"><a href="/login">ログインへ</a></p>
