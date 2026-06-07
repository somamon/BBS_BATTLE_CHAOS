<?php
/**
 * 新規登録フォーム。
 * @var string|null $error  ※コントローラで翻訳済みの文言
 * @var string      $email
 * @var string      $name
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2><?= t('register.title') ?></h2>

<?php if (!empty($error)): ?>
  <div class="error"><?= View::e($error) ?></div>
<?php endif; ?>

<?php if (!empty($googleEnabled)): ?>
  <p class="card" style="text-align:center;">
    <a href="/auth/google"><?= t('auth.google') ?></a>
  </p>
<?php endif; ?>

<form method="post" action="/register" class="card">
  <?= Csrf::field() ?>
  <label for="email"><?= t('register.email') ?></label>
  <input type="email" id="email" name="email" value="<?= View::e($email ?? '') ?>" required autofocus>
  <label for="name"><?= t('register.name') ?></label>
  <input type="text" id="name" name="name" value="<?= View::e($name ?? '') ?>" required>
  <label for="password"><?= t('register.password') ?></label>
  <input type="password" id="password" name="password" minlength="8" required>
  <div class="muted"><?= t('register.password_hint') ?></div>
  <label style="margin-top:10px;">
    <input type="checkbox" name="agree" value="1" <?= !empty($agree) ? 'checked' : '' ?> required>
    <?= t('register.agree') ?>
  </label>
  <button type="submit"><?= t('register.submit') ?></button>
</form>
<p class="muted"><?= t('register.have_account') ?> <a href="/login"><?= t('auth.login') ?></a></p>
