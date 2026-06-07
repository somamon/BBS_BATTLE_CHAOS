<?php
/**
 * 再送完了（列挙防止のため、アカウントの有無に関わらず同じ文面）。
 * @var string $email
 */
use App\Presentation\View\View;
?>
<h2><?= t('resend_done.title') ?></h2>

<div class="card">
  <p><?= t('resend_done.body', ['email' => View::e($email)]) ?></p>
  <p class="muted"><?= t('resend_done.note') ?></p>
</div>

<p class="muted"><a href="/login"><?= t('common.to_login') ?></a></p>
