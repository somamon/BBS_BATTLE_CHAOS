<?php
/**
 * お問い合わせフォーム。
 * @var string|null $error   ※コントローラで翻訳済み
 * @var string      $name
 * @var string      $email
 * @var string      $message
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2><?= t('contact.title') ?></h2>

<?php if (!empty($error)): ?>
  <div class="error"><?= View::e($error) ?></div>
<?php endif; ?>

<p class="muted"><?= t('contact.intro') ?></p>

<form method="post" action="/contact" class="card">
  <?= Csrf::field() ?>
  <label for="name"><?= t('contact.name') ?></label>
  <input type="text" id="name" name="name" value="<?= View::e($name ?? '') ?>" maxlength="50">
  <label for="email"><?= t('contact.email') ?></label>
  <input type="email" id="email" name="email" value="<?= View::e($email ?? '') ?>" required>
  <label for="message"><?= t('contact.message') ?></label>
  <textarea id="message" name="message" maxlength="2000" required><?= View::e($message ?? '') ?></textarea>
  <?php /* ハニーポット: 人間には見えない。ボット除けなので入力させない。 */ ?>
  <div style="position:absolute; left:-9999px;" aria-hidden="true">
    <label for="website">Website</label>
    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
  </div>
  <button type="submit"><?= t('contact.submit') ?></button>
</form>
