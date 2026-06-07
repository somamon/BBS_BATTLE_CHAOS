<?php
/**
 * ログインフォーム。
 * @var string|null $error  ※コントローラで翻訳済みの文言
 * @var string      $email
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2><?= t('login.title') ?></h2>

<?php if (!empty($error)): ?>
  <div class="error"><?= View::e($error) ?></div>
<?php endif; ?>

<form method="post" action="/login" class="card">
  <?= Csrf::field() ?>
  <label for="email"><?= t('login.email') ?></label>
  <input type="email" id="email" name="email" value="<?= View::e($email ?? '') ?>" required autofocus>
  <label for="password"><?= t('login.password') ?></label>
  <input type="password" id="password" name="password" required>
  <button type="submit"><?= t('login.submit') ?></button>
</form>
<p class="muted"><?= t('login.no_account') ?> <a href="/register"><?= t('login.register_link') ?></a></p>
<p class="muted"><?= t('login.unverified_pre') ?> <a href="/verify/resend"><?= t('login.resend_link') ?></a></p>
<p class="muted"><a href="/password/forgot"><?= t('login.forgot_link') ?></a></p>
