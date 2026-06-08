<?php
/**
 * 通報フォーム。
 * @var string|null $error
 * @var string $type  post|thread
 * @var string $id
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2><?= t('report.title') ?></h2>

<?php if (!empty($error)): ?>
  <div class="error"><?= View::e($error) ?></div>
<?php endif; ?>

<p class="muted"><?= t('report.intro') ?></p>

<form method="post" action="/report" class="card">
  <?= Csrf::field() ?>
  <input type="hidden" name="type" value="<?= View::e($type) ?>">
  <input type="hidden" name="id" value="<?= View::e($id) ?>">
  <label for="reason"><?= t('report.reason') ?></label>
  <select id="reason" name="reason">
    <option value="spam"><?= t('report.reason.spam') ?></option>
    <option value="abuse"><?= t('report.reason.abuse') ?></option>
    <option value="illegal"><?= t('report.reason.illegal') ?></option>
    <option value="other"><?= t('report.reason.other') ?></option>
  </select>
  <label for="detail"><?= t('report.detail') ?></label>
  <textarea id="detail" name="detail" maxlength="500" placeholder="<?= View::e(t('report.detail_ph')) ?>"></textarea>
  <button type="submit"><?= t('report.submit') ?></button>
</form>
