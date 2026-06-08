<?php
/**
 * お問い合わせ管理。
 * @var array<int,array<string,mixed>> $messages
 * @var string|null $flash
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2>お問い合わせ</h2>

<?php if (!empty($flash)): ?>
  <div class="flash"><?= View::e($flash) ?></div>
<?php endif; ?>

<?php if ($messages === []): ?>
  <p>お問い合わせはありません。</p>
<?php else: ?>
<table>
  <thead><tr><th>差出人</th><th>メール</th><th>内容（抜粋）</th><th>状態</th><th>日時</th><th>操作</th></tr></thead>
  <tbody>
  <?php foreach ($messages as $m): ?>
    <tr style="<?= $m['status'] === 'done' ? 'opacity:.55;' : '' ?>">
      <td><?= View::e($m['name'] ?? '(未記入)') ?></td>
      <td><a href="mailto:<?= View::e($m['email']) ?>"><?= View::e($m['email']) ?></a></td>
      <td style="max-width:320px;"><?= View::e($m['excerpt']) ?></td>
      <td><?= $m['status'] === 'open' ? '<span class="badge suspended">未対応</span>' : '<span class="badge active">対応済み</span>' ?></td>
      <td><?= View::e($m['createdAt']) ?></td>
      <td>
        <?php if ($m['status'] === 'open'): ?>
          <form method="post" action="/admin/contact/<?= View::e($m['id']) ?>/done" style="margin:0;"><?= Csrf::field() ?><button class="btn">対応済み</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
