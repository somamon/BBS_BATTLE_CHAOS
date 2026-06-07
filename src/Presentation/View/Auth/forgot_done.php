<?php
/**
 * パスワード再設定メール送信完了（列挙防止のため一律の文言）。
 * @var string $email
 */
use App\Presentation\View\View;
?>
<h2><?= t('forgot_done.title') ?></h2>

<div class="card">
  <p><?= t('forgot_done.body', ['email' => View::e($email)]) ?></p>
  <p class="muted"><?= t('forgot_done.note') ?></p>
</div>
<p class="muted"><a href="/login"><?= t('common.to_login') ?></a></p>
