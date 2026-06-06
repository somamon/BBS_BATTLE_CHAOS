# PHP リファレンス補遺

11_PHPリファレンス.md の不足分。

---

## 1. 乱数（イベントシステムの核）

### 1.1 random_int — 暗号学的に安全な整数

```php
random_int(0, 100);              // 0〜100（両端含む）
random_int(1, 6);                // サイコロ
random_int(0, PHP_INT_MAX);
```

**重要**: `rand()` / `mt_rand()` は使わない。`random_int()` 一択。
- 偏りがない
- 同じ範囲指定で予測困難
- 例外（`random_int` は `Error` を投げうる）

### 1.2 random_bytes — バイト列

```php
random_bytes(16);                // 16バイトのバイナリ
bin2hex(random_bytes(16));       // 32文字の16進文字列
```

### 1.3 加重ランダム選択（イベント抽選の必須パターン）

各イベントに重みが付いていて、重み合計から選ぶ実装:

```php
// 重み定義
$weights = [
    'meteor' => 10,
    'red_rain' => 8,
    'blackout' => 5,
    'garbled_text' => 15,
    'healing_light' => 12,
];

// 合計を出して 1..total の乱数で選ぶ
$total = array_sum($weights);
$pick = random_int(1, $total);

$accumulated = 0;
foreach ($weights as $event => $weight) {
    $accumulated += $weight;
    if ($pick <= $accumulated) {
        return $event;
    }
}
```

**応用: 倍率付き重み**

```php
// 条件によって重みを動的に変える
$dynamicWeights = [];
foreach ($baseWeights as $event => $weight) {
    $multiplier = $this->getMultiplier($event, $worldState);
    if ($multiplier <= 0) continue;  // 解禁前は除外
    $dynamicWeights[$event] = (int)($weight * $multiplier);
}
```

### 1.4 確率判定（X%で発生）

```php
function chance(int $percent): bool {
    return random_int(1, 100) <= $percent;
}

if (chance(15)) {
    // 15%で発火
}
```

### 1.5 配列からランダム選択

```php
$weathers = ['sunny', 'red_rain', 'fog', 'storm'];
$key = array_rand($weathers);          // ランダムキー（int）
$picked = $weathers[$key];

// 内部で mt_rand 使ってるので、ガチ用途は random_int 経由が安全
$picked = $weathers[random_int(0, count($weathers) - 1)];

// シャッフル
shuffle($weathers);  // 破壊的、戻り値は bool
```

---

## 2. 静的ファクトリ / 名前付きコンストラクタ（VO設計の核）

PHPは1クラス1コンストラクタなので、複数生成方法は `static` で。

```php
final class ThreadId
{
    private function __construct(
        public readonly string $value,
    ) {}

    // 新規生成
    public static function generate(): self
    {
        return new self(self::generateUlid());
    }

    // 既存値からの復元（DBから読み込む時）
    public static function fromString(string $value): self
    {
        if (!preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value)) {
            throw new InvalidArgumentException("Invalid ULID: $value");
        }
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function generateUlid(): string
    {
        // 実装は後述
    }
}

// 使う側
$id = ThreadId::generate();
$id = ThreadId::fromString($row['id']);
echo $id;  // __toString が呼ばれる
```

### 2.1 with〜 メソッド（readonly の更新パターン）

readonly プロパティは変更不可なので、新しいインスタンスを返す:

```php
final class HitPoint
{
    public function __construct(public readonly int $value) {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException();
        }
    }

    public function decrease(int $amount): self {
        return new self(max(0, $this->value - $amount));
    }

    public function increase(int $amount): self {
        return new self(min(100, $this->value + $amount));
    }
}

// 使う側
$hp = new HitPoint(100);
$hp = $hp->decrease(30);   // 新インスタンス
$hp = $hp->decrease(50);
echo $hp->value;           // 20
```

---

## 3. マジックメソッド（使いどころ）

| メソッド | 用途 |
|---|---|
| `__construct()` | 初期化 |
| `__toString()` | 文字列キャスト時、`echo` 時 |
| `__invoke()` | オブジェクトを関数のように呼ぶ |

```php
class PostContent {
    public function __construct(public readonly string $value) {}
    public function __toString(): string { return $this->value; }
}

$c = new PostContent('hello');
echo $c;                    // 'hello'
$str = (string)$c;          // 'hello'
$str = "値: $c";            // 'hello'
```

**注意**: `__toString()` で例外を投げると致命的エラー（PHP 7.4以降は許容されたが避ける）。

---

## 4. 抽象クラス / 継承

ドメインイベント基底クラスなどで使う:

```php
abstract class DomainEvent
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new DateTimeImmutable();
    }

    abstract public function name(): string;
}

final class PostCreated extends DomainEvent
{
    public function __construct(
        public readonly PostId $postId,
        public readonly ThreadId $threadId,
    ) {
        parent::__construct();
    }

    public function name(): string {
        return 'post.created';
    }
}
```

**ポイント**:
- `abstract class` はインスタンス化不可
- `abstract function` は実装必須
- `parent::__construct()` で親初期化
- 子クラスは `final` 推奨

---

## 5. 型キャスト

```php
(int)$value          // 整数化（'abc'→0, '12abc'→12, '1.7'→1）
(string)$value       // 文字列化
(float)$value        // 浮動小数化
(bool)$value         // 真偽化
(array)$value        // 配列化

intval($value, 10);  // 第2引数で基数指定
strval($value);
floatval($value);
boolval($value);
```

### 5.1 truthy / falsy（要注意）

**false 扱いの値**:

```php
false
0
0.0
''           // 空文字列
'0'          // 文字列の'0'（重要）
null
[]           // 空配列
```

**罠**:

```php
if ($value) { ... }        // '0' を弾いてしまう
if (empty($value)) { ... } // '0' や 0 や '' を全部空扱い
if ($value !== '') { ... } // 厳密に空文字列だけ判定
if ($value !== null) { ... }
isset($value)              // null と未定義を区別しない（両方false）
array_key_exists('k', $a)  // 値がnullでもキー存在ならtrue
```

**使い分け**:

| 判定したいこと | 使う関数 |
|---|---|
| 未定義 or null | `!isset($x)` |
| 値が空文字列 | `$x === ''` |
| 配列に該当キー（値がnullでも） | `array_key_exists('k', $a)` |
| 空配列 or 空文字列 or null | `empty($x)` |
| 厳密に値が等しい | `===` （`==` は型変換するので使わない） |

---

## 6. 配列の頻出操作

### 6.1 検索・抽出

```php
in_array('a', $arr, true);          // 存在判定（第3引数 true で厳密比較）
array_search('a', $arr, true);      // キーを返す、見つからなければ false
array_keys($arr);                   // 全キー
array_values($arr);                 // 全値（連番リセット）
array_unique($arr);                 // 重複除去
```

### 6.2 変換・整形

```php
array_map(fn($t) => $t->id, $threads);
array_filter($arr, fn($v) => $v > 0);
array_reduce($arr, fn($carry, $v) => $carry + $v, 0);
array_combine($keys, $values);
array_flip($arr);                    // キーと値を反転

array_slice($arr, 0, 10);            // 先頭10件
array_reverse($arr);
array_merge($a, $b);                 // 連結（数値キーは振り直し）
$merged = [...$a, ...$b];            // スプレッド構文

array_chunk($arr, 5);                // 5個ずつに分割
```

### 6.3 集計

```php
count($arr);
array_sum($arr);
max($arr);
min($arr);
array_count_values($arr);            // 値の出現回数
```

### 6.4 ソート

```php
sort($arr);                          // 昇順（破壊的）
rsort($arr);                         // 降順
usort($arr, fn($a, $b) => $a->hp - $b->hp);   // 独自比較
asort($arr);                         // 値でソート、キー保持
ksort($arr);                         // キーでソート
```

### 6.5 リスト分解（destructuring）

```php
[$first, $second] = $array;
['id' => $id, 'name' => $name] = $row;   // 連想配列分解

// foreach でも
foreach ($rows as ['id' => $id, 'name' => $name]) {
    // ...
}
```

---

## 7. 数値・算術

```php
abs(-5);                  // 5
min(1, 2, 3); max(1, 2);  // 可変引数 or 配列
round(1.5);               // 2
round(1.234, 2);          // 1.23
floor(1.9);               // 1
ceil(1.1);                // 2
pow(2, 10);  // 1024
sqrt(16);    // 4
intdiv(10, 3);            // 3（整数除算）
10 % 3;                   // 1（剰余）

PHP_INT_MAX
PHP_INT_MIN
PHP_FLOAT_EPSILON

// 範囲クランプ（よく使う）
$hp = max(0, min(100, $value));
```

---

## 8. PDO 追加パターン

### 8.1 IN 句（プレースホルダ動的生成）

```php
$ids = ['a', 'b', 'c'];
$placeholders = implode(',', array_fill(0, count($ids), '?'));
// → '?,?,?'

$sql = "SELECT * FROM threads WHERE id IN ($placeholders)";
$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$rows = $stmt->fetchAll();
```

### 8.2 LIKE エスケープ（投稿検索など）

```php
$keyword = $userInput;
$escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword);

$stmt = $pdo->prepare("SELECT * FROM posts WHERE content LIKE :kw ESCAPE '\\'");
$stmt->execute(['kw' => '%' . $escaped . '%']);
```

### 8.3 直近インサート ID（AUTOINCREMENT時）

```php
$pdo->lastInsertId();   // 文字列で返る
```

ULID使うなら不要。

### 8.4 NULL バインド

```php
$stmt->execute([
    'target_id' => $targetId,  // string or null どちらでもOK
]);

// 明示するなら
$stmt->bindValue(':target_id', $targetId, $targetId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
```

### 8.5 エラー情報取得（デバッグ）

```php
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    error_log($e->getMessage());
    error_log(print_r($pdo->errorInfo(), true));
    throw $e;
}
```

### 8.6 SQLite 接続オプション完全版

```php
$pdo = new PDO(
    'sqlite:' . __DIR__ . '/../database/database.sqlite',
    null,
    null,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ]
);

$pdo->exec('PRAGMA journal_mode = WAL');
$pdo->exec('PRAGMA synchronous = NORMAL');
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec('PRAGMA busy_timeout = 5000');  // ロック競合時の待機(ms)
```

---

## 9. 日時 詳細

### 9.1 タイムゾーン設定（深夜判定で必須）

```php
// 全体設定（index.php 先頭で1度だけ）
date_default_timezone_set('Asia/Tokyo');

// インスタンス単位
$tz = new DateTimeZone('Asia/Tokyo');
$now = new DateTimeImmutable('now', $tz);
$now = $now->setTimezone(new DateTimeZone('UTC'));
```

**罠**: タイムゾーン未設定だと UTC 扱いになり、深夜判定が9時間ズレる。

### 9.2 format 書式一覧

| 文字 | 意味 | 例 |
|---|---|---|
| Y | 年4桁 | 2026 |
| m | 月2桁 | 05 |
| d | 日2桁 | 30 |
| H | 時24h2桁 | 23 |
| G | 時24h（先頭0なし） | 0〜23 |
| i | 分2桁 | 45 |
| s | 秒2桁 | 12 |
| U | UNIXタイムスタンプ | 1748563200 |
| N | 曜日（月=1〜日=7） | 6 |
| w | 曜日（日=0〜土=6） | 5 |

### 9.3 比較・差分

```php
$a < $b;                    // 直接比較OK
$a->getTimestamp();         // UNIX time取得
$interval = $a->diff($b);   // DateInterval
$interval->days;            // 日数差
$interval->h;               // 時間差
$interval->i;               // 分差
```

### 9.4 modify でよく使うパターン

```php
$now->modify('+30 minutes');
$now->modify('+1 day');
$now->modify('-1 hour');
$now->modify('first day of this month');
$now->modify('next monday');
```

### 9.5 期限チェックパターン

```php
$now = new DateTimeImmutable();
$expired = $now > $challenge->endsAt;

// 残り秒数
$remaining = $challenge->endsAt->getTimestamp() - $now->getTimestamp();
```

---

## 10. 文字列 追加

### 10.1 ヒアドキュメント（HTML埋め込みに）

```php
$html = <<<HTML
<div class="event">
    <span>{$event->message}</span>
</div>
HTML;
```

- `<<<HTML` の `HTML` は任意の識別子
- 変数展開される
- 終端の `HTML;` は行頭から（PHP 7.3+ ではインデント可）

### 10.2 Nowdoc（変数展開なし）

```php
$sql = <<<'SQL'
    SELECT * FROM threads
    WHERE id = :id
SQL;
```

シングルクォート付きで変数展開なし。SQLなど。

### 10.3 文字列フォーマット

```php
sprintf('HP: %d/%d', $hp, $max);       // 'HP: 73/100'
sprintf('%.2f', 1.2345);                // '1.23'
sprintf('%05d', 42);                    // '00042'
sprintf('%s さん', $name);
str_pad('5', 3, '0', STR_PAD_LEFT);     // '005'
str_repeat('=', 50);
```

### 10.4 explode / implode

```php
explode(',', 'a,b,c');        // ['a','b','c']
implode(',', ['a','b','c']);  // 'a,b,c'
implode('', $chars);          // 連結
```

### 10.5 大文字小文字

```php
strtolower('ABC');
strtoupper('abc');
ucfirst('hello');
mb_strtolower('ＡＢＣ', 'UTF-8');
```

---

## 11. 正規表現（PCRE）

### 11.1 基本

```php
preg_match('/^[0-9]+$/', $str);              // 0/1
preg_match('/(?<year>\d{4})-(?<m>\d{2})/', $str, $matches);
// $matches['year'], $matches['m']

preg_match_all('/\d+/', $str, $matches);    // 全マッチ
preg_replace('/\s+/', ' ', $str);            // 置換
preg_replace_callback('/\d+/', fn($m) => $m[0] * 2, $str);
preg_split('/[\s,]+/', $str);                // 分割
```

### 11.2 デリミタとフラグ

```php
'/pattern/i'    // 大小無視
'/pattern/u'    // UTF-8（日本語扱う時必須）
'/pattern/m'    // 複数行
'/pattern/s'    // .が改行含む
'#pattern#'     // / 含むパターン用に # も使える
```

### 11.3 よく使うパターン

```php
'/^[0-9A-HJKMNP-TV-Z]{26}$/'    // ULID（Crockford Base32）
'/^[a-f0-9]{64}$/'               // SHA256
'/^[\p{L}\p{N}\s]+$/u'           // 文字・数字・空白（Unicode対応）
'/^[^\x00-\x1F]+$/u'             // 制御文字以外
```

---

## 12. ULID 簡易実装

Composerパッケージなしで作る。Crockford Base32 のエンコードがやや面倒。

### 12.1 簡易版（ULID互換）

```php
function generateUlid(): string
{
    $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    // 時間部分（48bit = 10文字）
    $time = (int)(microtime(true) * 1000);
    $timePart = '';
    for ($i = 9; $i >= 0; $i--) {
        $timePart .= $alphabet[$time % 32];
        $time = intdiv($time, 32);
    }
    $timePart = strrev($timePart);

    // ランダム部分（80bit = 16文字）
    $randPart = '';
    for ($i = 0; $i < 16; $i++) {
        $randPart .= $alphabet[random_int(0, 31)];
    }

    return $timePart . $randPart;
}
```

### 12.2 ULIDが面倒なら代替

完全なULID形式が必須でなければ:

```php
function generateId(): string {
    // UUIDv4風（36文字）
    $bytes = random_bytes(16);
    $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
    $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

// もっと簡単に
function simpleId(): string {
    return bin2hex(random_bytes(16));   // 32文字16進
}
```

---

## 13. CLI 実行（マイグレーション・シード用）

### 13.1 スクリプト実行

```
php database/migrate.php
php database/seed.php
```

### 13.2 CLI 専用判定

```php
if (PHP_SAPI !== 'cli') {
    die('CLIで実行してください');
}
```

### 13.3 引数取得

```php
// php migrate.php up 003
$_SERVER['argv'];     // ['migrate.php', 'up', '003']
$_SERVER['argc'];     // 3

$command = $argv[1] ?? 'up';
$version = $argv[2] ?? null;
```

### 13.4 標準出力

```php
echo "Migration started\n";
fwrite(STDOUT, "info\n");
fwrite(STDERR, "error\n");

// 改行コード
PHP_EOL    // 環境に応じた改行
```

### 13.5 終了コード

```php
exit(0);     // 成功
exit(1);     // エラー
```

### 13.6 PHP内蔵サーバ

```
php -S localhost:8080 -t public
```

| オプション | 意味 |
|---|---|
| `-S host:port` | サーバ起動 |
| `-t dir` | ドキュメントルート |
| `router.php` | ルータースクリプト（任意） |

ファイルが実在すればそのまま返す、なければ router.php に流れる。

### 13.7 文法チェック

```
php -l path/to/file.php    // 構文チェックのみ
php -r 'echo "hello";'     // ワンライナー
php -i                     // phpinfo
php -m                     // 有効モジュール一覧
```

---

## 14. デバッグ・ログ

### 14.1 dump系

```php
var_dump($data);              // 型・値を詳細
print_r($data);               // 配列・オブジェクト可読出力
var_export($data, true);      // PHP式として出力（trueで文字列返却）

// 改行付きdump
function dd(...$vars): never {
    foreach ($vars as $v) var_dump($v);
    exit;
}
```

### 14.2 ログ

```php
error_log('debug message');                              // PHPのerror_logへ
error_log(json_encode($data, JSON_UNESCAPED_UNICODE));   // 構造体は JSON 化
error_log('error', 3, __DIR__ . '/logs/app.log');        // 任意ファイル
```

### 14.3 例外スタックトレース

```php
try {
    // ...
} catch (Throwable $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
    error_log($e->getFile() . ':' . $e->getLine());
}
```

### 14.4 開発時のエラー表示

```php
// 開発時は必ず先頭で
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
```

---

## 15. 関数・クロージャ

### 15.1 アロー関数（PHP 7.4+）

```php
$double = fn($x) => $x * 2;
$mapped = array_map(fn($t) => $t->id, $threads);

// 自動で外部変数キャプチャ
$factor = 3;
$multiply = fn($x) => $x * $factor;  // 自動キャプチャ
```

### 15.2 通常クロージャ

```php
$threshold = 50;
$filter = function($v) use ($threshold) {
    return $v > $threshold;
};

// 参照キャプチャ
$counter = 0;
$inc = function() use (&$counter) {
    $counter++;
};
```

### 15.3 First-class callable（PHP 8.1+）

```php
$callable = $obj->method(...);          // 既存メソッドを Closure 化
$callable = strlen(...);                // 関数を Closure 化
$callable = SomeClass::staticMethod(...);
```

---

## 16. PSR-4 ディレクトリマッピング 詳細

`composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

**マッピング規則**:

| クラス（FQCN） | ファイルパス |
|---|---|
| `App\Domain\Entity\Thread` | `src/Domain/Entity/Thread.php` |
| `App\UseCase\Post\CreatePost` | `src/UseCase/Post/CreatePost.php` |
| `App\Infrastructure\Persistence\SQLite\SQLiteThreadRepository` | `src/Infrastructure/Persistence/SQLite/SQLiteThreadRepository.php` |

**条件**:
- 1ファイル1クラス
- ファイル名 = クラス名 + `.php`
- ディレクトリ階層 = 名前空間階層
- 大文字小文字一致（Linux/macOSは厳密）

### 16.1 オートロード再生成

クラス追加時:

```
composer dump-autoload          # オートロード再生成
composer dump-autoload -o       # 最適化（本番）
```

`vendor/autoload.php` が PHPファイルマップを更新する。

### 16.2 vendor 不要で動かす場合

Composerなしの簡易オートローダー:

```php
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require $file;
});
```

---

## 17. 出力バッファリング（View 用）

### 17.1 基本

```php
ob_start();
echo 'output';
$captured = ob_get_clean();   // 取得＋バッファ終了

ob_start();
require 'template.php';
$html = ob_get_clean();
```

### 17.2 入れ子可能

```php
ob_start();                   // レイヤー1
ob_start();                   // レイヤー2
echo 'inner';
$inner = ob_get_clean();      // レイヤー2終了
echo "wrapped: $inner";
$outer = ob_get_clean();      // レイヤー1終了
```

### 17.3 関連関数

| 関数 | 説明 |
|---|---|
| `ob_start()` | 開始 |
| `ob_get_contents()` | 取得（バッファ残す） |
| `ob_get_clean()` | 取得 + 終了 |
| `ob_end_clean()` | 破棄 |
| `ob_end_flush()` | 出力 + 終了 |
| `ob_get_level()` | 入れ子レベル |

---

## 18. フィルター関数

### 18.1 入力サニタイズ

```php
filter_var($email, FILTER_VALIDATE_EMAIL);   // emailっぽいか
filter_var($url, FILTER_VALIDATE_URL);
filter_var($num, FILTER_VALIDATE_INT);
filter_var('  abc  ', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
filter_var($ip, FILTER_VALIDATE_IP);
```

### 18.2 用途

CSRFやお題違反のチェックには使わない（自前バリデータの方が柔軟）。
IP検証など限定的に使う。

---

## 19. HTTPステータスコード早見

| コード | 意味 | 使い所 |
|---|---|---|
| 200 | OK | 成功 |
| 201 | Created | リソース作成成功 |
| 204 | No Content | 成功・本文なし |
| 302 | Found | リダイレクト |
| 304 | Not Modified | キャッシュ有効 |
| 400 | Bad Request | リクエスト不正 |
| 401 | Unauthorized | 認証必要 |
| 403 | Forbidden | 操作不許可 |
| 404 | Not Found | リソース無し |
| 405 | Method Not Allowed | メソッド違反 |
| 422 | Unprocessable Entity | バリデーションエラー |
| 429 | Too Many Requests | レートリミット |
| 500 | Internal Server Error | サーバエラー |
| 503 | Service Unavailable | メンテナンス等 |

---

## 20. セッション 詳細

### 20.1 設定（index.php 先頭）

```php
// セキュリティ設定
ini_set('session.cookie_httponly', '1');   // JSからのアクセス禁止
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_secure', '0');     // HTTPSなら 1

session_start();
```

### 20.2 主要関数

```php
session_start();
session_id();              // 現在のセッションID
session_regenerate_id(true);   // ID再生成（ログイン後）
session_destroy();
$_SESSION = [];
session_unset();

// 個別削除
unset($_SESSION['key']);
```

### 20.3 CSRF実装パターン

```php
// トークン生成（未生成時）
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// フォームに埋め込み
?>
<input type="hidden" name="_csrf" value="<?= e($_SESSION['csrf_token']) ?>">
<?php

// 検証
function verifyCsrf(string $token): bool {
    if (empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}
```

---

## 21. 環境変数 / 設定

### 21.1 取得

```php
getenv('APP_ENV');                     // string|false
$_ENV['APP_ENV'] ?? 'production';      // ini設定要
$_SERVER['HTTP_HOST'];
```

### 21.2 .env 簡易読込

PHP標準にはないが、自作で:

```php
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}
```

vlucas/phpdotenv パッケージ使うのが本当は楽だが、自作で十分。

### 21.3 定数

```php
define('APP_ROOT', __DIR__ . '/..');
const DB_PATH = '/path/to/db.sqlite';   // class外/関数外で使う場合
```

クラス内:

```php
final class Config {
    public const MAX_THREADS = 100;
    public const DEFAULT_HP = 100;
}

Config::MAX_THREADS;
```

---

## 22. PHP の罠（追加分）

| 罠 | 対策 |
|---|---|
| `==` で文字列と数値比較 | 必ず `===` |
| `$_POST` がHTMXで取れない | `Content-Type: application/x-www-form-urlencoded` を確認 |
| `header()` 後に出力するとエラー | BOMやスペースに注意、`<?php` の前に何も書かない |
| ファイル末尾の `?>` | 省略する（末尾の空白で `header()` エラー回避） |
| `htmlspecialchars(null)` がPHP 8.1で deprecated | `htmlspecialchars($x ?? '')` |
| 配列キーの順序 | PHPの配列は順序保持されるが信頼しすぎない |
| `count()` を `for` の条件式に | ループ毎に評価される、変数化する |
| `foreach` 後の `$v` が残る | 後で同名変数使うとバグる、`unset($v)` |
| `intdiv(10, 0)` | DivisionByZeroError |
| `0.1 + 0.2 !== 0.3` | 浮動小数点誤差、金額系はintで持つ |
| 日本語の長さ `strlen()` | UTF-8 で3倍長を返す、`mb_strlen` |
| マルチバイトソート | `sort` は ASCII順、日本語ソートは別途 |
| `is_numeric('0x1A')` | trueになる、用途で `ctype_digit` 検討 |
| `array_merge` の数値キー | 連番に振り直される、`+` 演算子は保持 |
| `preg_match` の戻り値 | 0(マッチなし)/1(マッチ)/false(エラー)、`=== 1` 推奨 |

---

## 23. デファクト命名規約

| 種別 | 規約 | 例 |
|---|---|---|
| クラス | PascalCase | `ThreadRepository` |
| インターフェース | 末尾 `Interface` | `ThreadRepositoryInterface` |
| 抽象クラス | 接頭 `Abstract` または末尾 `Base` | `AbstractRepository` |
| 例外 | 末尾 `Exception` | `ValidationException` |
| メソッド | camelCase | `findById` |
| プロパティ | camelCase | `$createdAt` |
| 定数 | UPPER_SNAKE | `MAX_HP` |
| ファイル | クラス名と同じ | `Thread.php` |
| DBテーブル | snake_case 複数形 | `threads`, `posts` |
| DBカラム | snake_case | `created_at` |

---

## 24. ディレクトリ・パス操作

```php
__DIR__                          // 現在のファイルのディレクトリ
__FILE__                         // 現在のファイルパス
dirname($path);                  // 親ディレクトリ
dirname($path, 2);               // 2階層上
basename($path);                 // ファイル名のみ
pathinfo($path, PATHINFO_EXTENSION);
realpath($path);                 // 絶対パス（存在しない場合は false）

// パス結合
$path = __DIR__ . '/../database/database.sqlite';
$path = implode(DIRECTORY_SEPARATOR, ['var', 'log', 'app.log']);

// ディレクトリ作成
mkdir($path, 0755, true);        // 第3引数 true で親も作る
is_dir($path);
```

---

## 25. このプロジェクトで使わないもの（誘惑回避）

| 機能 | 理由 |
|---|---|
| `eval()` | 絶対NG |
| `extract()` | 変数が暗黙的に増えるので使わない |
| `goto` | 使わない |
| 動的呼び出し `$method()` | 限定的、call_user_func系は使わない |
| グローバル変数 `global $x` | DIで解決 |
| 静的状態 `static $cache` | テストしにくい、Repository層に閉じる |
| `error_reporting(0)` | エラーは原因を直す |
| `@` エラー抑制 | 隠さない |
| 短縮タグ `<?` | 使わず `<?php`（`<?=` はOK） |
