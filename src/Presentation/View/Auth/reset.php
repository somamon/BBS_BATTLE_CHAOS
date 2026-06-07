<?php
/**
 * 新しいパスワードの設定フォーム。
 * @var string|null $error  ※コントローラで翻訳済みの文言
 * @var string      $token  メールリンクで渡された生トークン
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2><?= t('reset.title') ?></h2>

<?php if (!empty($error)): ?>
  <div class="error"><?= View::e($error) ?></div>
<?php endif; ?>

<form method="post" action="/password/reset" class="card">
  <?= Csrf::field() ?>
  <input type="hidden" name="token" value="<?= View::e($token ?? '') ?>">
  <label for="password"><?= t('reset.password') ?></label>
  <input type="password" id="password" name="password" minlength="8" required autofocus>
  <div class="muted"><?= t('register.password_hint') ?></div>
  <button type="submit"><?= t('reset.submit') ?></button>
</form>
<p class="muted"><a href="/login"><?= t('common.to_login') ?></a></p>
