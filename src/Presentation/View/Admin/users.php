<?php
/**
 * ユーザー管理。
 * @var array<int,array<string,mixed>> $users  id,name,email,role,status,money,createdAt
 * @var string|null $flash
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2>ユーザー</h2>

<?php if (!empty($flash)): ?>
  <div class="flash"><?= View::e($flash) ?></div>
<?php endif; ?>

<?php if ($users === []): ?>
  <p>ユーザーがいません。</p>
<?php else: ?>
<table>
  <thead>
    <tr><th>表示名</th><th>メール</th><th>ロール</th><th>状態</th><th>所持金</th><th>登録</th><th>操作</th></tr>
  </thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= View::e($u['name']) ?></td>
      <td><?= View::e($u['email']) ?></td>
      <td><?php if ($u['role'] === 'admin'): ?><span class="badge admin">admin</span><?php else: ?>user<?php endif; ?></td>
      <td>
        <?php if ($u['status'] === 'active'): ?>
          <span class="badge active">有効</span>
        <?php else: ?>
          <span class="badge suspended">凍結</span>
        <?php endif; ?>
      </td>
      <td><?= View::e(number_format((int) $u['money'])) ?></td>
      <td><?= View::e($u['createdAt']) ?></td>
      <td>
        <?php if ($u['role'] !== 'admin'): ?>
          <?php if ($u['status'] === 'active'): ?>
            <form method="post" action="/admin/users/<?= View::e($u['id']) ?>/suspend" style="margin:0;" onsubmit="return confirm('このユーザーを凍結しますか？');">
              <?= Csrf::field() ?>
              <button type="submit" class="btn danger">凍結</button>
            </form>
          <?php else: ?>
            <form method="post" action="/admin/users/<?= View::e($u['id']) ?>/unsuspend" style="margin:0;">
              <?= Csrf::field() ?>
              <button type="submit" class="btn">解除</button>
            </form>
          <?php endif; ?>
        <?php else: ?>
          <span style="color:#aaa;">—</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
