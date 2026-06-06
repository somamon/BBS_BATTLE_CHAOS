<?php
/**
 * スレッド作成フォーム。
 * @var string|null $error
 * @var string      $title
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<p><a href="/threads">← スレ一覧へ</a></p>
<h2>新しいスレッド</h2>

<?php if (!empty($error)): ?>
  <div class="error"><?= View::e($error) ?></div>
<?php endif; ?>

<form method="post" action="/threads" class="card">
  <?= Csrf::field() ?>
  <label for="title">タイトル</label>
  <input type="text" id="title" name="title" maxlength="255" value="<?= View::e($title ?? '') ?>" required autofocus>
  <button type="submit">立てる</button>
</form>
