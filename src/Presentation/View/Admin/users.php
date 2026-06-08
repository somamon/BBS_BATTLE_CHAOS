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
        <?php if (!empty($u['banned'])): ?>
          <span class="badge suspended">BAN</span>
        <?php elseif ($u['status'] === 'active'): ?>
          <span class="badge active">有効</span>
        <?php else: ?>
          <span class="badge suspended">凍結</span>
        <?php endif; ?>
      </td>
      <td><?= View::e(number_format((int) $u['money'])) ?></td>
      <td><?= View::e($u['createdAt']) ?></td>
      <td style="display:flex; gap:6px; flex-wrap:wrap;">
        <?php if ($u['role'] === 'admin'): ?>
          <span style="color:#aaa;">—</span>
        <?php elseif (!empty($u['banned'])): ?>
          <form method="post" action="/admin/users/<?= View::e($u['id']) ?>/unban" style="margin:0;">
            <?= Csrf::field() ?><button type="submit" class="btn">BAN解除</button>
          </form>
        <?php else: ?>
          <?php if ($u['status'] === 'active'): ?>
            <form method="post" action="/admin/users/<?= View::e($u['id']) ?>/suspend" style="margin:0;" data-confirm="このユーザーを凍結しますか？">
              <?= Csrf::field() ?><button type="submit" class="btn">凍結</button>
            </form>
          <?php else: ?>
            <form method="post" action="/admin/users/<?= View::e($u['id']) ?>/unsuspend" style="margin:0;">
              <?= Csrf::field() ?><button type="submit" class="btn">凍結解除</button>
            </form>
          <?php endif; ?>
          <form method="post" action="/admin/users/<?= View::e($u['id']) ?>/ban" style="margin:0; display:flex; gap:4px;" data-confirm="このユーザーをBANしますか？（ログイン・投資を禁止）">
            <?= Csrf::field() ?>
            <select name="days" style="font-size:11px;"><option value="0">無期限</option><option value="1">1日</option><option value="7">7日</option><option value="30">30日</option></select>
            <button type="submit" class="btn danger">BAN</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
