<?php
/**
 * 設定（バランス上書き・メンテ・アナウンス）。
 * @var array<string,string> $balance
 * @var bool $maintenance
 * @var string $announcement
 * @var string|null $flash
 */
use App\Presentation\Http\Csrf;
use App\Presentation\View\View;
?>
<h2>設定</h2>

<?php if (!empty($flash)): ?>
  <div class="flash"><?= View::e($flash) ?></div>
<?php endif; ?>

<form method="post" action="/admin/settings" class="card">
  <?= Csrf::field() ?>

  <h3 style="margin-top:0;">サイト</h3>
  <label><input type="checkbox" name="maintenance" value="1" <?= $maintenance ? 'checked' : '' ?>> メンテナンスモード（公開側を停止。管理画面は利用可）</label>
  <label for="announcement" style="margin-top:8px;">アナウンス（公開トップに表示。空で非表示）</label>
  <textarea id="announcement" name="announcement" maxlength="255"><?= View::e($announcement) ?></textarea>

  <h3>ゲームバランス上書き</h3>
  <p class="muted" style="font-size:12px;">空欄で保存すると上書きを解除し、env / 既定値に戻ります（解決順: DB → env → 既定）。</p>
  <table>
    <thead><tr><th>キー</th><th>上書き値（空=既定）</th></tr></thead>
    <tbody>
    <?php foreach ($balance as $key => $val): ?>
      <tr>
        <td style="font-family:monospace; font-size:12px;"><?= View::e($key) ?></td>
        <td><input type="text" name="<?= View::e($key) ?>" value="<?= View::e($val) ?>" style="width:160px;"></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <button type="submit" style="margin-top:12px;">保存</button>
</form>
