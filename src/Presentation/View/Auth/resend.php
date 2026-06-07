<?php
/**
 * 確認メール再送フォーム。
 * @var string|null $error
 * @var string      $email
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2>確認メールの再送</h2>

<?php if (!empty($error)): ?>
  <div class="error"><?= View::e($error) ?></div>
<?php endif; ?>

<p class="muted">登録に使ったメールアドレスを入力してください。未確認の場合のみ、確認メールを再送します。</p>

<form method="post" action="/verify/resend" class="card">
  <?= Csrf::field() ?>
  <label for="email">メールアドレス</label>
  <input type="email" id="email" name="email" value="<?= View::e($email ?? '') ?>" required autofocus>
  <button type="submit">確認メールを再送する</button>
</form>

<p class="muted"><a href="/login">ログインへ</a></p>
