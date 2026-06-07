<?php
/**
 * パスワード再設定の申請フォーム。
 * @var string|null $error  ※コントローラで翻訳済みの文言
 * @var string      $email
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2><?= t('forgot.title') ?></h2>

<?php if (!empty($error)): ?>
  <div class="error"><?= View::e($error) ?></div>
<?php endif; ?>

<p class="muted"><?= t('forgot.intro') ?></p>

<form method="post" action="/password/forgot" class="card">
  <?= Csrf::field() ?>
  <label for="email"><?= t('forgot.email') ?></label>
  <input type="email" id="email" name="email" value="<?= View::e($email ?? '') ?>" required autofocus>
  <button type="submit"><?= t('forgot.submit') ?></button>
</form>
<p class="muted"><a href="/login"><?= t('common.to_login') ?></a></p>
