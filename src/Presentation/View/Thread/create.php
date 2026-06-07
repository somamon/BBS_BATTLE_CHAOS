<?php
/**
 * スレッド作成フォーム。
 * @var string|null $error  ※コントローラで翻訳済みの文言
 * @var string      $title
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<p><a href="/threads"><?= t('common.back_to_threads') ?></a></p>
<h2><?= t('thread_create.title') ?></h2>

<?php if (!empty($error)): ?>
  <div class="error"><?= View::e($error) ?></div>
<?php endif; ?>

<form method="post" action="/threads" class="card">
  <?= Csrf::field() ?>
  <label for="title"><?= t('thread_create.label') ?></label>
  <input type="text" id="title" name="title" maxlength="255" value="<?= View::e($title ?? '') ?>" required autofocus>
  <button type="submit"><?= t('thread_create.submit') ?></button>
</form>
