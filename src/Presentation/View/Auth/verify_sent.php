<?php
/**
 * 確認メール送信完了。
 * @var string $email
 */
use App\Presentation\View\View;
?>
<h2><?= t('verify_sent.title') ?></h2>

<div class="card">
  <p><?= t('verify_sent.body', ['email' => View::e($email)]) ?></p>
  <p class="muted"><?= t('verify_sent.note1') ?></p>
  <p class="muted"><?= t('verify_sent.note2') ?></p>
</div>

<p class="muted"><?= t('verify_sent.resend_pre') ?> <a href="/verify/resend"><?= t('login.resend_link') ?></a> <?= t('verify_sent.resend_post') ?></p>
<p><a href="/login"><?= t('common.to_login') ?></a></p>
