# PHP 必須メソッド・構文リファレンス

ネット遮断中の開発用。本プロジェクトで使うPHP標準機能の一覧。

---

## 1. 言語構文

### 1.1 strict_types

ファイル先頭で型厳格モード。**全PHPファイルの1行目に書く**。

```php
<?php
declare(strict_types=1);
```

### 1.2 名前空間 / use

```php
namespace App\Domain\Entity;

use App\Domain\ValueObject\ThreadId;
use App\Domain\Enum\ThreadStatus;
```

### 1.3 クラス定義（PHP 8.x 機能フル活用）

```php
final class Thread
{
    public function __construct(
        public readonly ThreadId $id,
        public readonly ThreadTitle $title,
        private HitPoint $hp,
        private Corruption $corruption,
    ) {}

    public function takeDamage(int $amount): void
    {
        $this->hp = $this->hp->decrease($amount);
    }

    public function isAlive(): bool
    {
        return $this->hp->value() > 0;
    }
}
```

**ポイント**:
- `final class` — 継承禁止（基本これ）
- `readonly` — 変更不可プロパティ（VOやIDに）
- コンストラクタプロパティプロモーション — `public function __construct(public readonly ...)` で代入不要
- 戻り値型必須 — `: void`, `: bool`, `: ThreadId`

### 1.4 Enum（PHP 8.1+）

```php
enum ThreadStatus: string
{
    case Active = 'active';
    case Collapsed = 'collapsed';
    case Mutated = 'mutated';
    case Frozen = 'frozen';

    public function isTerminal(): bool
    {
        return $this === self::Collapsed;
    }
}

// 使う側
$status = ThreadStatus::Active;
$status->value;            // 'active'
$status->name;             // 'Active'
ThreadStatus::from('active');     // 厳格変換（不一致は例外）
ThreadStatus::tryFrom('xxx');     // 緩い変換（不一致はnull）
ThreadStatus::cases();            // 全ケースの配列
```

### 1.5 match 式（PHP 8+、switchより推奨）

```php
$multiplier = match ($phase) {
    WorldPhase::Calm => 1.0,
    WorldPhase::Unstable => 1.2,
    WorldPhase::Chaos => 1.5,
    WorldPhase::Apocalypse => 2.0,
};
```

- 厳密比較（===）
- 全ケース網羅必須（漏れると UnhandledMatchError）
- 式なので変数代入可能

### 1.6 Null合体・Null安全

```php
$title = $_POST['title'] ?? '';        // null/未定義時の代替
$title ??= 'デフォルト';                // null代入演算子
$length = $thread?->title?->length();  // null安全演算子（nullなら全体null）
```

### 1.7 例外

```php
throw new InvalidArgumentException('タイトルは1〜100文字');
throw new DomainException('崩壊スレには投稿できません');
throw new RuntimeException('DB接続失敗');

try {
    $useCase->execute($input);
} catch (ValidationException $e) {
    return $this->response->error(422, $e->getMessage());
} catch (DomainException $e) {
    return $this->response->error(403, $e->getMessage());
} catch (Throwable $e) {
    error_log($e->getMessage());
    return $this->response->error(500, 'Internal Server Error');
}
```

### 1.8 インターフェース / 抽象

```php
interface ThreadRepositoryInterface
{
    public function findById(ThreadId $id): ?Thread;
    public function save(Thread $thread): void;
    public function findAll(int $limit = 50): array;
}
```

### 1.9 配列操作で頻出

```php
array_map(fn($t) => $t->id, $threads);    // 変換
array_filter($events, fn($e) => $e->isWorldEvent());  // 抽出
array_merge($a, $b);                       // 結合
in_array($value, $array, true);            // 厳密検索
count($array);                             // 個数
array_keys($array);                        // キー一覧
empty($array);                             // 空判定
```

---

## 2. HTTP / リクエスト

### 2.1 リクエスト情報取得

```php
$_SERVER['REQUEST_METHOD']        // 'GET', 'POST'
$_SERVER['REQUEST_URI']           // '/thread/abc?x=1'
$_SERVER['REMOTE_ADDR']           // クライアントIP
$_SERVER['HTTP_HOST']             // 'localhost:8080'
$_SERVER['HTTP_X_REQUESTED_WITH'] // 任意HTTPヘッダ
$_SERVER['HTTP_HX_REQUEST']       // HTMX判定（'true'）

$_GET['key']                      // クエリパラメータ
$_POST['key']                     // POST本体
$_COOKIE['key']                   // クッキー
```

### 2.2 URLパース

```php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// '/thread/abc?x=1' → '/thread/abc'

$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
// → 'x=1'

parse_str($query, $params);  // 文字列→配列
```

### 2.3 レスポンス送信

```php
http_response_code(200);
http_response_code(302);
http_response_code(404);
http_response_code(422);
http_response_code(500);

header('Content-Type: text/html; charset=UTF-8');
header('Location: /thread/abc');         // リダイレクト
header('Cache-Control: no-store');

echo $html;
exit;
```

### 2.4 セッション

```php
session_start();                    // ファイル先頭で呼ぶ
$_SESSION['csrf_token'] = $token;
$_SESSION['rate']['post'] = time();

session_regenerate_id(true);        // セキュリティ向上
session_destroy();
```

---

## 3. PDO（SQLite/MySQL共通）

### 3.1 接続

```php
// SQLite
$pdo = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');

// MySQL（将来）
$pdo = new PDO(
    'mysql:host=localhost;dbname=bbs;charset=utf8mb4',
    'user',
    'password'
);

// 必須設定
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);     // 例外モード
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // 連想配列
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);             // 真のプリペアド

// SQLite 推奨設定
$pdo->exec('PRAGMA journal_mode = WAL');
$pdo->exec('PRAGMA foreign_keys = ON');
```

### 3.2 DDL（テーブル作成、生SQL）

```php
$pdo->exec("
    CREATE TABLE IF NOT EXISTS threads (
        id TEXT PRIMARY KEY,
        title TEXT NOT NULL,
        hp INTEGER NOT NULL DEFAULT 100,
        created_at TEXT NOT NULL
    )
");

$pdo->exec("CREATE INDEX IF NOT EXISTS idx_threads_created_at ON threads(created_at)");
```

### 3.3 SELECT（プリペアド必須）

```php
// 1件取得
$stmt = $pdo->prepare('SELECT * FROM threads WHERE id = :id');
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();   // false なら未取得
if ($row === false) {
    return null;
}

// 複数取得
$stmt = $pdo->prepare('SELECT * FROM threads ORDER BY created_at DESC LIMIT :limit');
$stmt->bindValue('limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// 件数取得
$stmt = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE thread_id = :tid');
$stmt->execute(['tid' => $threadId]);
$count = (int)$stmt->fetchColumn();
```

### 3.4 INSERT / UPDATE / DELETE

```php
$stmt = $pdo->prepare('
    INSERT INTO posts (id, thread_id, content, author_hash, created_at)
    VALUES (:id, :thread_id, :content, :author_hash, :created_at)
');
$stmt->execute([
    'id' => $post->id->value(),
    'thread_id' => $post->threadId->value(),
    'content' => $post->content->value(),
    'author_hash' => $post->authorHash->value(),
    'created_at' => $post->createdAt->format(DateTimeInterface::ATOM),
]);

// UPDATE
$stmt = $pdo->prepare('UPDATE threads SET hp = :hp WHERE id = :id');
$stmt->execute(['hp' => $hp, 'id' => $id]);

$affected = $stmt->rowCount();  // 影響行数
```

### 3.5 トランザクション

```php
$pdo->beginTransaction();
try {
    $pdo->prepare('UPDATE threads SET hp = ?')->execute([$hp]);
    $pdo->prepare('INSERT INTO posts ...')->execute([...]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}
```

### 3.6 プリペアドの注意

| パターン | 使う |
|---|---|
| WHERE/VALUES の値 | `:placeholder` で必ずバインド |
| LIMIT/OFFSET の数値 | `bindValue(..., PDO::PARAM_INT)` 必要 |
| カラム名・テーブル名 | プレースホルダ不可、ホワイトリストで分岐 |
| ORDER BY の方向 | プレースホルダ不可、`'ASC'`/`'DESC'` を分岐 |

---

## 4. 文字列操作

### 4.1 出力エスケープ（最重要）

```php
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

echo e($post->content->value());
```

**ENT_QUOTES** はシングル/ダブルクォート両方をエスケープ。
View では `<?= e($var) ?>` をパターン化。

### 4.2 長さ・分割・検索

```php
mb_strlen($s, 'UTF-8');              // マルチバイト長（必ずmb_系）
mb_substr($s, 0, 100, 'UTF-8');      // 部分取得
mb_strpos($s, '禁止語');             // 検索
trim($s);                            // 前後空白除去
str_contains($s, '隕石');            // 含むか（PHP 8+）
str_starts_with($s, 'http');         // 前方一致（PHP 8+）
str_ends_with($s, '.png');           // 後方一致（PHP 8+）
str_replace('a', 'b', $s);           // 置換
preg_match('/^[0-9A-Z]{26}$/', $s);  // 正規表現マッチ
preg_replace('/\s+/', ' ', $s);      // 正規表現置換
```

### 4.3 JSON

```php
$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);  // true で連想配列
```

### 4.4 sprintf / 数値フォーマット

```php
sprintf('HP: %d/%d', $current, $max);
number_format(1234567);                 // '1,234,567'
```

---

## 5. 日時

### 5.1 DateTimeImmutable（推奨、Mutableは使わない）

```php
$now = new DateTimeImmutable();
$now = new DateTimeImmutable('2026-05-30 12:34:56');

$now->format('Y-m-d H:i:s');                    // '2026-05-30 12:34:56'
$now->format(DateTimeInterface::ATOM);          // ISO 8601: '2026-05-30T12:34:56+09:00'

$tomorrow = $now->modify('+1 day');             // 新しいインスタンス返却
$plus30min = $now->modify('+30 minutes');

// 比較
$now < $other;                                   // 直接比較可能
$diff = $now->diff($other);                      // DateInterval

// タイムスタンプ
$now->getTimestamp();
```

### 5.2 時刻判定（深夜帯チェック）

```php
$hour = (int)$now->format('G');  // 0〜23
$isNight = $hour >= 0 && $hour < 5;
```

---

## 6. セキュリティ・ハッシュ

```php
// CSRFトークン生成
$token = bin2hex(random_bytes(32));

// タイミング攻撃対策（CSRFトークン比較）
hash_equals($expected, $actual);

// SHA256（AuthorHash用）
hash('sha256', $ip . $salt);

// パスワード（今回未使用だが参考）
password_hash($plain, PASSWORD_DEFAULT);
password_verify($plain, $hash);

// ULID生成（自作する場合のシード）
random_bytes(10);
```

---

## 7. ファイル操作

```php
file_exists($path);
is_readable($path);
file_get_contents($path);
file_put_contents($path, $data, LOCK_EX);  // ロック付き書き込み

require __DIR__ . '/../vendor/autoload.php';
require_once $path;                         // 一度だけ読込
```

---

## 8. オートロード（Composer）

`composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "require": {
        "php": ">=8.3"
    }
}
```

設定後:

```
composer dump-autoload
```

エントリポイントで:

```php
require __DIR__ . '/../vendor/autoload.php';
```

これだけで `App\Domain\Entity\Thread` が自動ロードされる。

---

## 9. エラー設定（開発時）

`public/index.php` 先頭付近で:

```php
error_reporting(E_ALL);
ini_set('display_errors', '1');   // 開発時
ini_set('display_errors', '0');   // 本番
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');

set_exception_handler(function (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo 'Internal Server Error';
});
```

---

## 10. ULID 生成（自作）

ULIDはComposerパッケージ無しで簡易実装可能。

```
ULID形式: 26文字、Crockford Base32
構成: タイムスタンプ48bit + ランダム80bit
```

自作する場合の必要素材:

```php
$time = (int)(microtime(true) * 1000);  // ミリ秒
$random = random_bytes(10);              // 80bit
// → Crockford Base32 (0-9A-HJKMNP-TV-Z) でエンコード
```

簡易代替: ULID形式が必須でなければ `bin2hex(random_bytes(13))` で26桁の16進文字列でもOK。

---

## 11. 内蔵サーバ起動

開発時:

```
php -S localhost:8080 -t public
```

`public/` をドキュメントルートとして起動。
全リクエストは `public/index.php` に流れる（存在しないファイルは404）。

ルーティングをindex.phpに集約するには:

```
php -S localhost:8080 public/index.php
```

としても良いが、CSS/JS等が配信されなくなる。前者推奨。

---

## 12. View でのパターン

### 12.1 レイアウトの埋め込み

`layout.php`:

```php
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= e($title ?? '異常掲示板') ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body data-weather="<?= e($weather) ?>" data-phase="<?= e($phase) ?>">
    <?= $content ?>
</body>
</html>
```

各ページ:

```php
<?php
$title = 'スレッド詳細';
ob_start();
?>
<h1><?= e($thread->title->value()) ?></h1>
<!-- ... -->
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
```

`ob_start()` / `ob_get_clean()` で出力をキャプチャして変数に格納。

### 12.2 HTMX 部分更新用テンプレ

partial は **layoutを使わず** HTML片のみを返す。

```php
<div id="thread-state">
    <div class="hp-bar">
        <div class="hp-bar__fill" style="width: <?= $thread->hp->value() ?>%"></div>
        <span>HP: <?= $thread->hp->value() ?>/100</span>
    </div>
</div>
```

---

## 13. HTMX 主要属性（参考）

| 属性 | 説明 |
|---|---|
| `hx-get="/path"` | GET送信 |
| `hx-post="/path"` | POST送信 |
| `hx-trigger="every 3s"` | 3秒間隔ポーリング |
| `hx-trigger="click"` | クリック時 |
| `hx-target="#id"` | 差し込み先要素 |
| `hx-swap="innerHTML"` | innerHTMLに差替（既定） |
| `hx-swap="beforeend"` | 末尾に追加 |
| `hx-swap="outerHTML"` | 要素ごと差替 |
| `hx-on::after-request="..."` | レスポンス後JS実行 |
| `hx-vals='{"key":"val"}'` | 追加パラメータ |
| `hx-include="#form"` | 他要素の値も送信 |

サーバ側で `HTTP_HX_REQUEST` ヘッダで判別可能。

---

## 14. ファイル先頭テンプレ

新規ファイル作成時の雛形:

```php
<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ThreadId;

final class Thread
{
    public function __construct(
        public readonly ThreadId $id,
    ) {}
}
```

---

## 15. やりがちなミスと対策

| ミス | 対策 |
|---|---|
| 文字列長を `strlen` で測る | `mb_strlen($s, 'UTF-8')` を使う |
| エスケープ忘れ | View では必ず `e()` |
| PDO で SQL文字列連結 | 必ずプリペアド + バインド |
| 配列キーの未定義アクセス | `$arr['k'] ?? null` で null合体 |
| readonly プロパティに再代入 | 新しいインスタンスを返す（`with〜`メソッド） |
| Enum を文字列と比較 | `$status === ThreadStatus::Active` または `->value === 'active'` |
| LIMIT にプレースホルダ → 文字列扱い | `bindValue` で `PDO::PARAM_INT` 指定 |
| セッション開始忘れ | `session_start()` を index.php 先頭で |
| `header()` 後に出力 → エラー | `header()` は出力より先に |
| `htmlspecialchars` で文字化け | 第3引数 `'UTF-8'` 必須 |
