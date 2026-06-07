<?php
/**
 * 退会（アカウント削除）の確認ページ。
 */
use App\Presentation\Http\Csrf;
?>
<h2><?= t('account.delete.title') ?></h2>

<div class="card">
  <p><?= t('account.delete.lead') ?></p>
  <ul>
    <li><?= t('account.delete.item_account') ?></li>
    <li><?= t('account.delete.item_assets') ?></li>
    <li><?= t('account.delete.item_posts') ?></li>
  </ul>
  <p class="error" style="margin-top:8px;"><?= t('account.delete.warning') ?></p>
</div>

<form method="post" action="/account/delete" class="card">
  <?= Csrf::field() ?>
  <label>
    <input type="checkbox" name="confirm" value="1" required>
    <?= t('account.delete.confirm_label') ?>
  </label>
  <button type="submit" style="border-color:#cc0000; color:#cc0000;"><?= t('account.delete.submit') ?></button>
</form>
<p class="muted"><a href="/me"><?= t('account.delete.cancel') ?></a></p>
