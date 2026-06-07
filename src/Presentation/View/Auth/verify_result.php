<?php
/**
 * メール確認の失敗表示（成功時は /threads へリダイレクトするため、ここは失敗のみ）。
 * @var string $message  ※コントローラで翻訳済みの文言
 */
use App\Presentation\View\View;
?>
<h2><?= t('verify_result.title') ?></h2>

<div class="error"><?= View::e($message) ?></div>

<p class="muted"><?= t('verify_result.retry_pre') ?> <a href="/register"><?= t('verify_result.retry_link') ?></a> <?= t('verify_result.retry_post') ?></p>
