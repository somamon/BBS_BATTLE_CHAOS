<?php
/**
 * 新規登録フォーム。
 * @var string|null $error
 * @var string      $email
 * @var string      $name
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2>新規登録</h2>

<?php if (!empty($error)): ?>
  <div class="error"><?= View::e($error) ?></div>
<?php endif; ?>

<form method="post" action="/register" class="card">
  <?= Csrf::field() ?>
  <label for="email">メールアドレス</label>
  <input type="email" id="email" name="email" value="<?= View::e($email ?? '') ?>" required autofocus>
  <label for="name">表示名</label>
  <input type="text" id="name" name="name" value="<?= View::e($name ?? '') ?>" required>
  <label for="password">パスワード</label>
  <input type="password" id="password" name="password" minlength="8" required>
  <div class="muted">8文字以上で設定してください。</div>
  <button type="submit">登録する</button>
</form>
<p class="muted">すでにアカウントをお持ちですか？ <a href="/login">ログイン</a></p>
