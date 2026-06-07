<?php
/**
 * メール確認の失敗表示（成功時は /threads へリダイレクトするため、ここは失敗のみ）。
 * @var string $message
 */
use App\Presentation\View\View;
?>
<h2>メール確認</h2>

<div class="error"><?= View::e($message) ?></div>

<p class="muted">お手数ですが、もう一度 <a href="/register">新規登録</a> からやり直してください。</p>
