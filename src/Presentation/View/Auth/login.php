<?php
/**
 * ログインフォーム。
 * @var string|null $error
 * @var string      $email
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2>ログイン</h2>

<?php if (!empty($error)): ?>
  <div class="error"><?= View::e($error) ?></div>
<?php endif; ?>

<form method="post" action="/login" class="card">
  <?= Csrf::field() ?>
  <label for="email">メールアドレス</label>
  <input type="email" id="email" name="email" value="<?= View::e($email ?? '') ?>" required autofocus>
  <label for="password">パスワード</label>
  <input type="password" id="password" name="password" required>
  <button type="submit">ログイン</button>
</form>
<p class="muted">アカウントがありませんか？ <a href="/register">新規登録</a></p>
<p class="muted">メール未確認の方は <a href="/verify/resend">確認メールを再送</a></p>
